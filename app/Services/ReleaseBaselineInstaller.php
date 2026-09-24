<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class ReleaseBaselineInstaller
{
    public const RESERVED_ADMIN_EMAIL = 'release.admin@platform.test';
    public const RESERVED_AMARA_EMAIL = 'release.practice.1@platform.test';
    public const RESERVED_DANIEL_EMAIL = 'release.practice.2@platform.test';
    public const RESERVED_SOFIA_EMAIL = 'release.practice.3@platform.test';

    public function import(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('The packaged release baseline requires MySQL or MariaDB.');
        }

        $baselinePath = database_path('baseline/release-baseline.sql.gz');
        $manifestPath = database_path('baseline/release-baseline.json');

        if (! is_file($baselinePath) || ! is_file($manifestPath)) {
            throw new RuntimeException('The packaged release baseline is unavailable.');
        }

        if (! function_exists('gzopen')) {
            throw new RuntimeException('PHP zlib support is required to install the packaged release baseline.');
        }

        $manifest = json_decode(File::get($manifestPath), true);

        if (! is_array($manifest) || blank($manifest['sha256'] ?? null)) {
            throw new RuntimeException('The release baseline manifest is invalid.');
        }

        $actualHash = hash_file('sha256', $baselinePath);

        if (! is_string($actualHash) || ! hash_equals(strtolower((string) $manifest['sha256']), strtolower($actualHash))) {
            throw new RuntimeException('The packaged release baseline failed its integrity check.');
        }

        $existingTables = (int) DB::scalar(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_type = 'BASE TABLE'"
        );

        if ($existingTables !== 0) {
            throw new RuntimeException('Release baseline import requires an empty database.');
        }

        $handle = gzopen($baselinePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('The packaged release baseline could not be opened.');
        }

        $statement = '';

        try {
            while (! gzeof($handle)) {
                $line = gzgets($handle);

                if ($line === false) {
                    break;
                }

                $trimmed = trim($line);

                if ($statement === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
                    continue;
                }

                $statement .= $line;

                if (! str_ends_with($trimmed, ';')) {
                    continue;
                }

                DB::connection('mysql')->getPdo()->exec($statement);
                $statement = '';
            }

            if (trim($statement) !== '') {
                throw new RuntimeException('The packaged release baseline ended with an incomplete SQL statement.');
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The packaged release baseline could not be imported.',
                previous: $exception
            );
        } finally {
            gzclose($handle);
        }
    }

    public function assertImportedAuthority(): void
    {
        $manifestPath = database_path('baseline/release-baseline.json');
        $manifest = json_decode(File::get($manifestPath), true);
        $expected = $manifest['expected'] ?? [];

        $tables = (int) DB::scalar(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_type = 'BASE TABLE'"
        );

        if ($tables !== (int) ($expected['tables'] ?? 0)) {
            throw new RuntimeException('Release baseline table authority is incomplete.');
        }

        foreach ([
            'users',
            'market_instruments',
            'stocks',
            'forex_pairs',
            'crypto_pairs',
            'commodity_instruments',
            'signals',
            'bot_products',
            'copy_trader_profiles',
            'copy_strategies',
            'languages',
            'language_translations',
        ] as $table) {
            $expectedCount = (int) ($expected[$table] ?? -1);
            $actualCount = (int) DB::table($table)->count();

            if ($actualCount !== $expectedCount) {
                throw new RuntimeException(
                    "Release baseline authority mismatch for {$table}: expected {$expectedCount}, found {$actualCount}."
                );
            }
        }

        foreach ([
            self::RESERVED_ADMIN_EMAIL,
            self::RESERVED_AMARA_EMAIL,
            self::RESERVED_DANIEL_EMAIL,
            self::RESERVED_SOFIA_EMAIL,
        ] as $email) {
            if (! DB::table('users')->where('email', $email)->exists()) {
                throw new RuntimeException('Release baseline reserved identity is missing: '.$email);
            }
        }
    }

    public function assertPersonalizedAuthority(string $adminEmail, string $demoDomain): void
    {
        $practiceEmails = [
            'amara.okafor@'.$demoDomain,
            'daniel.brooks@'.$demoDomain,
            'sofia.martinez@'.$demoDomain,
        ];

        if ((int) DB::table('users')->count() !== 4) {
            throw new RuntimeException('Installed user authority must contain exactly one administrator and three practice users.');
        }

        if ((int) DB::table('users')->where('is_admin', true)->count() !== 1) {
            throw new RuntimeException('Installed administrator authority is invalid.');
        }

        if (! DB::table('users')->where('email', $adminEmail)->where('is_admin', true)->exists()) {
            throw new RuntimeException('Installer-selected administrator identity is missing.');
        }

        foreach ($practiceEmails as $email) {
            if (! DB::table('users')->where('email', $email)->where('is_admin', false)->exists()) {
                throw new RuntimeException('Practice identity is missing: '.$email);
            }
        }

        foreach ([
            self::RESERVED_ADMIN_EMAIL,
            self::RESERVED_AMARA_EMAIL,
            self::RESERVED_DANIEL_EMAIL,
            self::RESERVED_SOFIA_EMAIL,
        ] as $reservedEmail) {
            if (DB::table('users')->where('email', $reservedEmail)->exists()) {
                throw new RuntimeException('Reserved release identity was not personalized: '.$reservedEmail);
            }
        }

        $providerCount = DB::table('copy_trader_profiles')
            ->join('users', 'users.id', '=', 'copy_trader_profiles.user_id')
            ->whereIn('users.email', $practiceEmails)
            ->count();

        if ((int) $providerCount !== 3) {
            throw new RuntimeException('Practice provider authority was not preserved.');
        }
    }
}