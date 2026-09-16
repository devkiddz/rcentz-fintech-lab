<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketEnvironment;
use App\Models\Setting;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Services\LiveMarketHealthService;
use App\Services\MailConfigurationService;
use App\Services\StockDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index(
        Request $request,
        MailConfigurationService $mailConfiguration,
        LiveMarketHealthService $marketHealthService,
        StockDataService $stockData
    ) {
        $section = (string) $request->query('section', 'overview');
        $allowed = ['overview', 'general', 'appearance', 'market', 'trading', 'security', 'mail', 'integrations', 'system'];

        if (! in_array($section, $allowed, true)) {
            $section = 'overview';
        }

        return match ($section) {
            'general' => $this->general(),
            'appearance' => $this->appearance(),
            'market' => $this->market($marketHealthService, $stockData),
            'trading' => $this->trading(),
            'security' => $this->security(),
            'mail' => $this->mail($mailConfiguration),
            'integrations' => $this->integrations($marketHealthService, $stockData),
            'system' => $this->system(),
            default => $this->overview($mailConfiguration),
        };
    }

    private function overview(MailConfigurationService $mailConfiguration)
    {
        $marketEnvironment = MarketEnvironment::current();
        $mailSnapshot = $mailConfiguration->snapshot();
        $summary = [
            'site_name' => Setting::get('site_name', config('app.name')),
            'price_source' => $marketEnvironment->active_marketplace === 'live' ? 'External Feed' : 'Internal Feed',
            'kyc' => Setting::isEnabled('enable_kyc'),
            'email_verification' => Setting::isEnabled('enable_email_verification'),
            'mail_transport' => (string) $mailSnapshot['mailer'],
            'storage_ready' => file_exists(public_path('storage')) || is_dir(public_path('storage')),
        ];

        return $this->settingsView('admin.settings.index', [
            'activeSettingsSection' => 'overview',
            'summary' => $summary,
        ]);
    }

    public function general()
    {
        return $this->renderGroup(
            'general',
            'General Settings',
            'Site identity, contact information, public metadata and application defaults.'
        );
    }

    public function appearance()
    {
        return $this->renderGroup(
            'appearance',
            'Appearance Settings',
            'Brand assets and public presentation settings.'
        );
    }

    public function security()
    {
        return $this->renderGroup(
            'security',
            'Security Settings',
            'Current identity and verification controls. More approval policy can be added here as those domains mature.'
        );
    }

    public function market(
        LiveMarketHealthService $marketHealthService,
        StockDataService $stockData
    ) {
        $marketEnvironment = MarketEnvironment::current();
        $marketHealth = $marketHealthService->snapshot($stockData);
        $marketExposure = [
            'external_positions' => TradePosition::query()
                ->where('marketplace', 'live')
                ->whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->count(),
            'internal_positions' => TradePosition::query()
                ->where('marketplace', 'controlled')
                ->whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->count(),
            'external_holdings' => StockHolding::query()
                ->where('marketplace', 'live')
                ->where('quantity', '>', 0)
                ->count(),
            'internal_holdings' => StockHolding::query()
                ->where('marketplace', 'controlled')
                ->where('quantity', '>', 0)
                ->count(),
        ];

        return $this->settingsView('admin.settings.market', [
            'activeSettingsSection' => 'market',
            'marketEnvironment' => $marketEnvironment,
            'marketHealth' => $marketHealth,
            'marketExposure' => $marketExposure,
        ]);
    }

    public function trading()
    {
        return $this->settingsView('admin.settings.trading', [
            'activeSettingsSection' => 'trading',
        ]);
    }

    public function mail(MailConfigurationService $mailConfiguration)
    {
        return $this->settingsView('admin.settings.mail', [
            'activeSettingsSection' => 'mail',
            'mailConfig' => $mailConfiguration->snapshot(),
        ]);
    }

    public function updateMail(Request $request, MailConfigurationService $mailConfiguration)
    {
        $data = $request->validate([
            'mail_mailer' => ['required', 'in:smtp,log,array'],
            'mail_host' => ['nullable', 'required_if:mail_mailer,smtp', 'string', 'max:255'],
            'mail_port' => ['nullable', 'required_if:mail_mailer,smtp', 'integer', 'min:1', 'max:65535'],
            'mail_scheme' => ['nullable', 'in:auto,smtps'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:4096'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $mailConfiguration->save($data);

            return redirect()->route('admin.settings.index', ['section' => 'mail'])
                ->with('success', 'Email configuration saved. New mail requests now use these settings.');
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Failed to save email configuration: '.$e->getMessage())
                ->withInput();
        }
    }

    public function integrations(
        LiveMarketHealthService $marketHealthService,
        StockDataService $stockData
    ) {
        return $this->settingsView('admin.settings.integrations', [
            'activeSettingsSection' => 'integrations',
            'marketHealth' => $marketHealthService->snapshot($stockData),
        ]);
    }

    public function system()
    {
        $system = [
            'environment' => app()->environment(),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'debug' => (bool) config('app.debug'),
            'storage_ready' => file_exists(public_path('storage')) || is_dir(public_path('storage')),
        ];

        return $this->settingsView('admin.settings.system', [
            'activeSettingsSection' => 'system',
            'system' => $system,
        ]);
    }

    public function updateGeneral(Request $request)
    {
        return $this->updateGroup($request, 'general');
    }

    public function updateAppearance(Request $request)
    {
        return $this->updateGroup($request, 'appearance');
    }

    public function updateSecurity(Request $request)
    {
        return $this->updateGroup($request, 'security');
    }

    /**
     * Legacy all-settings endpoint retained for compatibility.
     * New UI forms save one settings domain at a time.
     */
    public function update(Request $request)
    {
        return $this->saveSettings($request, Setting::all(), 'overview');
    }

    public function testMail(Request $request, MailConfigurationService $mailConfiguration)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $mailConfiguration->apply();

            Mail::raw(
                'This is a Rcentz administration mail-delivery test. If you received it, the saved mail configuration can deliver application email.',
                function ($message) use ($data) {
                    $message->to($data['test_email'])
                        ->subject('Rcentz mail delivery test');
                }
            );

            return redirect()->route('admin.settings.index', ['section' => 'mail'])
                ->with('success', 'Test email sent to '.$data['test_email'].'.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.index', ['section' => 'mail'])
                ->with('error', 'Mail test failed: '.$e->getMessage());
        }
    }

    public function clearCache()
    {
        try {
            Setting::clearCache();
            Cache::flush();

            return back()->with('success', 'Application cache cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear cache: '.$e->getMessage());
        }
    }

    public function resetToDefaults()
    {
        try {
            Setting::truncate();
            \Artisan::call('db:seed', ['--class' => 'SettingsSeeder']);
            Setting::clearCache();

            return redirect()->route('admin.settings.index', ['section' => 'overview'])
                ->with('success', 'Settings reset to defaults successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reset settings: '.$e->getMessage());
        }
    }

    public function fixStorage()
    {
        try {
            $this->rebuildPublicStorageLink();
            Setting::clearCache();
            Cache::flush();

            return back()->with('success', 'Public storage was normalized and linked successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to fix storage access: '.$e->getMessage());
        }
    }

    public function storageLink()
    {
        try {
            $this->rebuildPublicStorageLink();

            return back()->with('success', 'Storage link created successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to create storage link: '.$e->getMessage());
        }
    }

    public function clearConfig()
    {
        try {
            \Artisan::call('config:clear');
            return back()->with('success', 'Configuration cache cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear config cache: '.$e->getMessage());
        }
    }

    public function clearViews()
    {
        try {
            \Artisan::call('view:clear');
            return back()->with('success', 'Compiled views cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear views: '.$e->getMessage());
        }
    }

    public function clearRoutes()
    {
        try {
            \Artisan::call('route:clear');
            return back()->with('success', 'Route cache cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear routes: '.$e->getMessage());
        }
    }

    public function optimizeClear()
    {
        try {
            \Artisan::call('optimize:clear');
            Setting::clearCache();
            Cache::flush();

            return back()->with('success', 'All application caches cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear all caches: '.$e->getMessage());
        }
    }

    private function renderGroup(string $group, string $title, string $description)
    {
        return $this->settingsView('admin.settings.section', [
            'activeSettingsSection' => $group,
            'sectionTitle' => $title,
            'sectionDescription' => $description,
            'sectionSettings' => Setting::getByGroup($group),
            'updateRoute' => 'admin.settings.'.$group.'.update',
        ]);
    }

    private function updateGroup(Request $request, string $group)
    {
        return $this->saveSettings(
            $request,
            Setting::getByGroup($group),
            $group
        );
    }

    private function saveSettings(Request $request, Collection $settings, string $redirectSection)
    {
        $validationRules = [
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'string'],
        ];

        foreach ($settings->where('type', 'image') as $setting) {
            $validationRules["file_{$setting->key}"] = ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,ico', 'max:2048'];
        }

        $request->validate($validationRules);

        try {
            foreach ($settings as $setting) {
                $key = $setting->key;
                $value = $request->input("settings.$key", $setting->value);

                if ($setting->isPassword() && ($value === null || $value === '')) {
                    continue;
                }

                if ($setting->isImage() && $request->hasFile("file_{$key}")) {
                    $file = $request->file("file_{$key}");
                    if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    $value = $file->store('settings', 'public');
                }

                if ($setting->isCheckbox()) {
                    $value = $request->boolean("settings.$key") ? '1' : '0';
                }

                $setting->update(['value' => $value]);
            }

            Setting::clearCache();

            return redirect()->route('admin.settings.index', ['section' => $redirectSection])
                ->with('success', 'Settings updated successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to update settings: '.$e->getMessage())->withInput();
        }
    }

    private function settingsView(string $view, array $data = [])
    {
        return view($view, array_merge($data, [
            'settingsNavigation' => config('admin-settings.groups', []),
            'settingsSections' => config('admin-settings.sections', []),
        ]));
    }

    private function rebuildPublicStorageLink(): void
    {
        $publicStoragePath = public_path('storage');
        $storageAppPublicPath = storage_path('app/public');

        File::ensureDirectoryExists($storageAppPublicPath, 0755, true);

        if (is_link($publicStoragePath)) {
            if (! @unlink($publicStoragePath)) {
                throw new \RuntimeException('Could not remove the existing public/storage symbolic link.');
            }
        } elseif (is_dir($publicStoragePath)) {
            File::copyDirectory($publicStoragePath, $storageAppPublicPath);
            if (! File::deleteDirectory($publicStoragePath)) {
                throw new \RuntimeException('Could not remove the legacy public/storage directory.');
            }
        } elseif (file_exists($publicStoragePath)) {
            if (! @unlink($publicStoragePath)) {
                throw new \RuntimeException('Could not remove the existing public/storage path.');
            }
        }

        if (! @symlink($storageAppPublicPath, $publicStoragePath)) {
            throw new \RuntimeException(
                'The hosting account refused symbolic-link creation. Ask the host to allow symlinks or create public/storage -> storage/app/public from cPanel Terminal.'
            );
        }
    }
}
