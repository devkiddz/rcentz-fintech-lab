<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Setting;
use App\Models\User;
use App\Services\LocalizationService;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\InstallationDemoSeeder;
use Database\Seeders\LocalizationSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'languageRegistry' => config('localization.languages', []),
            'majorLocales' => config('localization.major_locales', ['en']),
            'timezones' => timezone_identifiers_list(),
            'currencies' => $this->currencies(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('adminlogin.login');
        }

        if (! $this->allRequirementsMet()) {
            return back()->withErrors(['install' => 'The server requirements are not satisfied yet.'])->withInput();
        }

        $languageCodes = array_keys(config('localization.languages', ['en' => []]));

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'company_name' => ['required', 'string', 'max:120'],
            'legal_company_name' => ['nullable', 'string', 'max:160'],
            'site_tagline' => ['required', 'string', 'max:180'],
            'site_description' => ['required', 'string', 'max:800'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:60'],
            'app_url' => ['required', 'url', 'max:255'],
            'app_env' => ['required', 'in:local,production'],
            'brand_primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_secondary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'default_locale' => ['required', Rule::in($languageCodes)],
            'enabled_locales' => ['required', 'array', 'min:1'],
            'enabled_locales.*' => ['required', Rule::in($languageCodes)],
            'default_timezone' => ['required', 'timezone:all'],
            'default_currency' => ['required', Rule::in(array_keys($this->currencies()))],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:128'],
            'db_username' => ['required', 'string', 'max:128'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:10', 'confirmed'],
            'demo_password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        if (! in_array($validated['default_locale'], $validated['enabled_locales'], true)) {
            return back()->withErrors(['default_locale' => 'The default language must also be enabled.'])->withInput();
        }

        try {
            $this->configureDatabase($validated);
            DB::connection('mysql')->getPdo();
            $this->writeEnvironment($validated);

            Artisan::call('config:clear');
            if (blank(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
                Artisan::call('config:clear');
            }

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => CoreDataSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => \Database\Seeders\SettingsSeeder::class, '--force' => true]);
            Artisan::call('db:seed', ['--class' => LocalizationSeeder::class, '--force' => true]);

            $this->applyBrandSettings($validated);
            $this->applyLocalizationSettings($validated);

            User::updateOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => Hash::make($validated['admin_password']),
                    'is_admin' => true,
                    'email_verified_at' => now(),
                    'currency' => $validated['default_currency'],
                    'locale' => $validated['default_locale'],
                ]
            );


            config(['bootstrap.installation_demo.password' => $validated['demo_password']]);
            Artisan::call('db:seed', ['--class' => InstallationDemoSeeder::class, '--force' => true]);

            $demoDomain = (Str::slug($validated['company_name']) ?: 'platform').'.test';
            try {
                Artisan::call('storage:link');
            } catch (Throwable $ignored) {
                // Existing public storage links are harmless during an intentional reinstall.
            }

            File::put($this->lockPath(), json_encode([
                'installed_at' => now()->toIso8601String(),
                'app_name' => $validated['app_name'],
                'company_name' => $validated['company_name'],
                'app_url' => $validated['app_url'],
                'default_locale' => $validated['default_locale'],
                'enabled_locales' => array_values(array_unique($validated['enabled_locales'])),
            ], JSON_PRETTY_PRINT));

            Artisan::call('optimize:clear');

            return redirect()->route('adminlogin.login')->with(
                'status',
                'Installation complete. Administrator access is ready. Practice accounts: amara.okafor@'.$demoDomain.', daniel.brooks@'.$demoDomain.' and sofia.martinez@'.$demoDomain.'. They use the practice password selected during installation.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['install' => 'Installation could not be completed. Review the application logs for technical details.'])
                ->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation', 'demo_password', 'demo_password_confirmation']));
        }
    }

    private function applyBrandSettings(array $values): void
    {
        $legalName = trim((string) ($values['legal_company_name'] ?? '')) ?: $values['company_name'];

        Setting::set('site_name', $values['app_name']);
        Setting::set('company_name', $values['company_name']);
        Setting::set('legal_company_name', $legalName);
        Setting::set('site_tagline', $values['site_tagline']);
        Setting::set('site_description', $values['site_description']);
        Setting::set('site_url', rtrim($values['app_url'], '/'));
        Setting::set('site_email', $values['support_email']);
        Setting::set('site_phone', $values['support_phone'] ?? '');
        Setting::set('brand_primary_color', strtolower($values['brand_primary_color']));
        Setting::set('brand_secondary_color', strtolower($values['brand_secondary_color']));
        Setting::set('footer_text', '© '.date('Y').' '.$legalName.'. All rights reserved.');
        Setting::set('developer_credit_enabled', '0');
        Setting::clearCache();
    }

    private function applyLocalizationSettings(array $values): void
    {
        $enabled = array_values(array_unique($values['enabled_locales']));

        Language::query()->update(['is_enabled' => false, 'is_default' => false]);
        Language::query()->whereIn('code', $enabled)->update(['is_enabled' => true]);
        Language::query()->where('code', $values['default_locale'])->update(['is_enabled' => true, 'is_default' => true]);

        Setting::set('default_locale', $values['default_locale']);
        Setting::set('default_timezone', $values['default_timezone']);
        Setting::set('default_currency', $values['default_currency']);
        app(LocalizationService::class)->clearCache();
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
        if (! File::exists($envPath)) File::copy(base_path('.env.example'), $envPath);

        $replacements = [
            'APP_NAME' => $values['app_name'],
            'APP_ENV' => $values['app_env'],
            'APP_DEBUG' => $values['app_env'] === 'local' ? 'true' : 'false',
            'APP_URL' => rtrim($values['app_url'], '/'),
            'APP_LOCALE' => $values['default_locale'],
            'APP_FALLBACK_LOCALE' => 'en',
            'APP_TIMEZONE' => $values['default_timezone'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $values['db_host'],
            'DB_PORT' => (string) $values['db_port'],
            'DB_DATABASE' => $values['db_database'],
            'DB_USERNAME' => $values['db_username'],
            'DB_PASSWORD' => $values['db_password'] ?? '',
            'MAIL_MAILER' => 'log',
            'MAIL_FROM_ADDRESS' => $values['support_email'],
            'MAIL_FROM_NAME' => $values['app_name'],
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
        ];

        $contents = File::get($envPath);
        foreach ($replacements as $key => $value) {
            $encoded = $this->envValue((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'\s*=.*$/m';
            $contents = preg_match($pattern, $contents)
                ? preg_replace($pattern, $key.'='.$encoded, $contents)
                : $contents.PHP_EOL.$key.'='.$encoded;
        }
        File::put($envPath, rtrim($contents).PHP_EOL);
    }

    private function envValue(string $value): string
    {
        if ($value === 'true' || $value === 'false' || $value === '') return $value;
        if (preg_match('/^[A-Za-z0-9_\.\-:\/]+$/', $value)) return $value;
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
        foreach ($this->requirements() as $requirement) if (! $requirement['ok']) return false;
        return true;
    }

    private function currencies(): array
    {
        return [
            'USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound', 'NGN' => 'Nigerian Naira',
            'CAD' => 'Canadian Dollar', 'AUD' => 'Australian Dollar', 'CHF' => 'Swiss Franc', 'JPY' => 'Japanese Yen',
            'CNY' => 'Chinese Yuan', 'INR' => 'Indian Rupee', 'ZAR' => 'South African Rand', 'AED' => 'UAE Dirham',
            'SGD' => 'Singapore Dollar', 'HKD' => 'Hong Kong Dollar', 'NZD' => 'New Zealand Dollar',
        ];
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
