<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class MailConfigurationService
{
    private const SECRET_PREFIX = 'enc:v1:';

    /**
     * Apply database-backed mail overrides to Laravel's runtime configuration.
     * Environment/config values remain the fallback when no override exists.
     */
    public function apply(): void
    {
        try {
            $settings = $this->settings();
            if ($settings->isEmpty()) {
                return;
            }

            $mailer = $this->value($settings, 'mail_mailer', (string) config('mail.default', 'log')) ?: 'log';
            $scheme = $this->value($settings, 'mail_scheme', config('mail.mailers.smtp.scheme'));
            $scheme = $scheme === 'auto' || $scheme === '' ? null : $scheme;

            $password = $this->secretValue($settings->get('mail_password'));
            if (! $settings->has('mail_password')) {
                $password = config('mail.mailers.smtp.password');
            }

            config([
                'mail.default' => $mailer,
                'mail.mailers.smtp.url' => null,
                'mail.mailers.smtp.scheme' => $scheme,
                'mail.mailers.smtp.host' => $this->nullableValue($settings, 'mail_host', config('mail.mailers.smtp.host')),
                'mail.mailers.smtp.port' => (int) ($this->value($settings, 'mail_port', config('mail.mailers.smtp.port', 2525)) ?: 2525),
                'mail.mailers.smtp.username' => $this->nullableValue($settings, 'mail_username', config('mail.mailers.smtp.username')),
                'mail.mailers.smtp.password' => $password,
                'mail.from.address' => $this->value($settings, 'mail_from_address', config('mail.from.address')),
                'mail.from.name' => $this->value($settings, 'mail_from_name', config('mail.from.name')),
            ]);
        } catch (\Throwable) {
            // Mail overrides must never prevent the application, migrations,
            // installer commands or first deployment from booting.
        }
    }

    public function snapshot(): array
    {
        $this->apply();

        try {
            $settings = $this->settings();
        } catch (\Throwable) {
            $settings = collect();
        }

        $scheme = config('mail.mailers.smtp.scheme');

        return [
            'mailer' => (string) config('mail.default', 'log'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'scheme' => $scheme ?: 'auto',
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'password_configured' => filled(config('mail.mailers.smtp.password')),
            'password_saved' => $settings->has('mail_password') && filled($settings->get('mail_password')?->value),
            'database_override' => $settings->isNotEmpty(),
        ];
    }

    public function save(array $data): void
    {
        $definitions = [
            'mail_mailer' => ['type' => 'text', 'label' => 'Mail Transport', 'description' => 'Application mail transport.'],
            'mail_host' => ['type' => 'text', 'label' => 'SMTP Host', 'description' => 'SMTP server hostname.'],
            'mail_port' => ['type' => 'text', 'label' => 'SMTP Port', 'description' => 'SMTP server port.'],
            'mail_scheme' => ['type' => 'text', 'label' => 'SMTP Security', 'description' => 'Automatic TLS or implicit SMTPS.'],
            'mail_username' => ['type' => 'text', 'label' => 'SMTP Username', 'description' => 'SMTP authentication username.'],
            'mail_from_address' => ['type' => 'text', 'label' => 'From Address', 'description' => 'Default sender email address.'],
            'mail_from_name' => ['type' => 'text', 'label' => 'From Name', 'description' => 'Default sender display name.'],
        ];

        foreach ($definitions as $key => $meta) {
            $this->put($key, $data[$key] ?? '', $meta['type'], $meta['label'], $meta['description']);
        }

        if (isset($data['mail_password']) && trim((string) $data['mail_password']) !== '') {
            $this->put(
                'mail_password',
                self::SECRET_PREFIX.Crypt::encryptString((string) $data['mail_password']),
                'password',
                'SMTP Password',
                'Encrypted SMTP authentication secret.'
            );
        }

        Setting::clearCache();
        $this->apply();
    }

    private function settings(): Collection
    {
        if (! Schema::hasTable('settings')) {
            return collect();
        }

        return Setting::query()
            ->where('group', 'mail')
            ->get()
            ->keyBy('key');
    }

    private function put(string $key, mixed $value, string $type, string $label, string $description): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value === null ? '' : (string) $value,
                'type' => $type,
                'group' => 'mail',
                'label' => $label,
                'description' => $description,
                'is_public' => false,
            ]
        );
    }

    private function value(Collection $settings, string $key, mixed $fallback = null): mixed
    {
        if (! $settings->has($key)) {
            return $fallback;
        }

        return $settings->get($key)?->value;
    }

    private function nullableValue(Collection $settings, string $key, mixed $fallback = null): mixed
    {
        $value = $this->value($settings, $key, $fallback);
        return $value === '' ? null : $value;
    }

    private function secretValue(?Setting $setting): ?string
    {
        if (! $setting || ! filled($setting->value)) {
            return null;
        }

        $value = (string) $setting->value;
        if (! str_starts_with($value, self::SECRET_PREFIX)) {
            // Compatibility with any pre-existing plaintext mail setting.
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::SECRET_PREFIX)));
        } catch (\Throwable) {
            return null;
        }
    }
}
