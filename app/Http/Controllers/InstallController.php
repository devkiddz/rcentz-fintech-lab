<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\LiveTestDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('adminlogin.login')
                ->with('status', 'The application is already installed.');
        }

        return view('install.index', [
            'requirements' => $this->requirements(),
            'allRequirementsMet' => $this->allRequirementsMet(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('adminlogin.login');
        }

        if (! $this->allRequirementsMet()) {
            return back()->withErrors([
                'install' => 'The server requirements are not satisfied yet.',
            ])->withInput();
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url', 'max:255'],
            'app_env' => ['required', 'in:local,production'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:128'],
            'db_username' => ['required', 'string', 'max:128'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:10', 'confirmed'],
            'seed_demo' => ['nullable', 'boolean'],
        ]);

        try {
            $this->configureDatabase($validated);
            DB::connection('mysql')->getPdo();

            $this->writeEnvironment($validated);

            // Rebuild configuration from the newly-written .env values.
            Artisan::call('config:clear');

            if (blank(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
                Artisan::call('config:clear');
            }

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', [
                '--class' => CoreDataSeeder::class,
                '--force' => true,
            ]);

            User::updateOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => Hash::make($validated['admin_password']),
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ]
            );

            if ($request->boolean('seed_demo')) {
                Artisan::call('db:seed', [
                    '--class' => LiveTestDataSeeder::class,
                    '--force' => true,
                ]);
            }

            try {
                Artisan::call('storage:link');
            } catch (Throwable $ignored) {
                // Existing links are harmless during reinstall/test cycles.
            }

            File::put($this->lockPath(), json_encode([
                'installed_at' => now()->toIso8601String(),
                'app_name' => $validated['app_name'],
                'app_url' => $validated['app_url'],
            ], JSON_PRETTY_PRINT));

            Artisan::call('optimize:clear');

            return redirect()->route('adminlogin.login')->with(
                'status',
                'Installation complete. Sign in with the administrator account you just created.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'install' => $exception->getMessage(),
            ])->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']));
        }
    }

    private function configureDatabase(array $values): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $values['db_host'],
            'database.connections.mysql.port' => (string) $values['db_port'],
            'database.connections.mysql.database' => $values['db_database'],
            'database.connections.mysql.username' => $values['db_username'],
            'database.connections.mysql.password' => $values['db_password'] ?? '',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function writeEnvironment(array $values): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $replacements = [
            'APP_NAME' => $values['app_name'],
            'APP_ENV' => $values['app_env'],
            'APP_DEBUG' => $values['app_env'] === 'local' ? 'true' : 'false',
            'APP_URL' => rtrim($values['app_url'], '/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $values['db_host'],
            'DB_PORT' => (string) $values['db_port'],
            'DB_DATABASE' => $values['db_database'],
            'DB_USERNAME' => $values['db_username'],
            'DB_PASSWORD' => $values['db_password'] ?? '',
            'MAIL_MAILER' => 'log',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
        ];

        $contents = File::get($envPath);

        foreach ($replacements as $key => $value) {
            $encoded = $this->envValue($value);
            $pattern = '/^'.preg_quote($key, '/').'\s*=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $key.'='.$encoded, $contents);
            } else {
                $contents .= PHP_EOL.$key.'='.$encoded;
            }
        }

        File::put($envPath, rtrim($contents).PHP_EOL);
    }

    private function envValue(string $value): string
    {
        if ($value === 'true' || $value === 'false' || $value === '') {
            return $value;
        }

        if (preg_match('/^[A-Za-z0-9_\.\-:\/]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    private function requirements(): array
    {
        return [
            ['label' => 'PHP 8.2 or newer', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['label' => 'PDO MySQL extension', 'ok' => extension_loaded('pdo_mysql')],
            ['label' => 'Mbstring extension', 'ok' => extension_loaded('mbstring')],
            ['label' => 'OpenSSL extension', 'ok' => extension_loaded('openssl')],
            ['label' => 'Fileinfo extension', 'ok' => extension_loaded('fileinfo')],
            ['label' => 'cURL extension', 'ok' => extension_loaded('curl')],
            ['label' => 'DOM/XML extension', 'ok' => extension_loaded('dom')],
            ['label' => 'Ctype extension', 'ok' => extension_loaded('ctype')],
            ['label' => 'Tokenizer extension', 'ok' => extension_loaded('tokenizer')],
            ['label' => 'storage/ is writable', 'ok' => is_writable(storage_path())],
            ['label' => 'bootstrap/cache is writable', 'ok' => is_writable(base_path('bootstrap/cache'))],
            ['label' => '.env or .env.example is available', 'ok' => File::exists(base_path('.env')) || File::exists(base_path('.env.example'))],
        ];
    }

    private function allRequirementsMet(): bool
    {
        foreach ($this->requirements() as $requirement) {
            if (! $requirement['ok']) {
                return false;
            }
        }

        return true;
    }

    private function isInstalled(): bool
    {
        return File::exists($this->lockPath());
    }

    private function lockPath(): string
    {
        return storage_path('app/installed');
    }
}
