<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::getGrouped();
        
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // Build validation rules
        $validationRules = [
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ];

        // Add file validation rules for image settings
        $imageSettings = Setting::where('type', 'image')->get();
        foreach ($imageSettings as $setting) {
            $validationRules["file_{$setting->key}"] = 'nullable|file|mimes:jpeg,png,jpg,gif,svg,ico|max:2048';
        }

        $request->validate($validationRules);

        try {
            
            // Iterate over ALL settings so image-only fields are handled
            $allSettings = Setting::all();
            foreach ($allSettings as $setting) {
                $key = $setting->key;
                $value = $request->input("settings.$key", $setting->value);

                // Handle file uploads for image settings
                if ($setting->isImage()) {
                    
                    
                    if ($request->hasFile("file_{$key}")) {
                        $file = $request->file("file_{$key}");
                        
                        // Delete old file if exists
                        if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                            Storage::disk('public')->delete($setting->value);
                        }

                        // Store new file
                        $path = $file->store('settings', 'public');
                        $value = $path;
                        
                    }
                }

                // Handle checkbox values
                if ($setting->isCheckbox()) {
                    $value = $request->boolean("settings.$key") ? '1' : '0';
                }

                $setting->update(['value' => $value]);
            }

            // Clear settings cache
            Setting::clearCache();

            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update settings. Please try again.')
                ->withInput();
        }
    }

    public function clearCache()
    {
        try {
            Setting::clearCache();
            Cache::flush();
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Cache cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clear cache. Please try again.');
        }
    }

    public function resetToDefaults()
    {
        try {
            // Delete all current settings
            Setting::truncate();
            
            // Run the seeder to restore defaults
            \Artisan::call('db:seed', ['--class' => 'SettingsSeeder']);
            
            // Clear cache
            Setting::clearCache();
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings reset to defaults successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reset settings. Please try again.');
        }
    }

    /**
     * Normalize the public storage link without losing files that may have
     * been copied into public/storage by an older deployment.
     */
    public function fixStorage()
    {
        try {
            $this->rebuildPublicStorageLink();
            Setting::clearCache();
            Cache::flush();

            return redirect()->route('admin.settings.index')
                ->with('success', 'Public storage was normalized and linked successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Failed to fix storage access: '.$e->getMessage());
        }
    }

    public function storageLink()
    {
        try {
            $this->rebuildPublicStorageLink();

            return redirect()->route('admin.settings.index')
                ->with('success', 'Storage link created successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Failed to create storage link: '.$e->getMessage());
        }
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
            // Preserve any legacy files before replacing the copied directory
            // with Laravel's canonical public storage link.
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

    public function clearConfig()
    {
        try {
            \Artisan::call('config:clear');
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Configuration cache cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clear config cache.');
        }
    }

    public function clearViews()
    {
        try {
            \Artisan::call('view:clear');
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Compiled views cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clear views.');
        }
    }

    public function clearRoutes()
    {
        try {
            \Artisan::call('route:clear');
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Route cache cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clear routes.');
        }
    }

    public function optimizeClear()
    {
        try {
            \Artisan::call('optimize:clear');
            Setting::clearCache();
            Cache::flush();
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'All caches cleared successfully (config, views, routes, cache, compiled).');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clear all caches.');
        }
    }
}
