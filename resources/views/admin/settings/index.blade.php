<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    Site Settings
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.settings.fix-storage') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Fix Storage
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.storage-link') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-tesla-600 text-white text-sm font-medium rounded-lg hover:bg-tesla-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                        Storage Link
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.clear-cache') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                        Clear Cache
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.clear-config') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        Clear Config
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.clear-views') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Clear Views
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.clear-routes') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-pink-600 text-white text-sm font-medium rounded-lg hover:bg-pink-700 transition-colors">
                        Clear Routes
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.optimize-clear') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-cyan-600 text-white text-sm font-medium rounded-lg hover:bg-cyan-700 transition-colors">
                        Clear All
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.settings.reset-defaults') }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')" 
                            class="px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Reset Defaults
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf
                
                <!-- Settings Groups -->
                <div class="space-y-6">
                    @foreach($settings as $group => $groupSettings)
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground capitalize">{{ str_replace('_', ' ', $group) }} Settings</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($groupSettings as $setting)
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-muted-foreground">
                                        {{ $setting->label }}
                                        @if($setting->description)
                                            <span class="text-xs text-muted-foreground block font-normal mt-1">{{ $setting->description }}</span>
                                        @endif
                                    </label>

                                    @if($setting->isCheckbox())
                                        <div class="flex items-center">
                                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                            <input type="checkbox" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   value="1" 
                                                   {{ $setting->value == '1' ? 'checked' : '' }}
                                                   class="w-4 h-4 text-foreground border-border rounded focus:ring-black focus:ring-2">
                                            <span class="ml-2 text-sm text-muted-foreground dark:text-gray-300">Enable {{ $setting->label }}</span>
                                        </div>
                                    @elseif($setting->isImage())
                                        <div class="space-y-3">
                                            @if($setting->value)
                                                <div class="flex items-center space-x-3">
                                                    @if(in_array(pathinfo($setting->value, PATHINFO_EXTENSION), ['svg', 'ico']))
                                                        <div class="w-16 h-16 bg-muted dark:bg-dark-muted rounded-lg flex items-center justify-center">
                                                            <img src="{{ asset('storage/' . $setting->value) }}" 
                                                                 alt="{{ $setting->label }}" 
                                                                 class="w-12 h-12 object-contain">
                                                        </div>
                                                    @else
                                                        <img src="{{ asset('storage/' . $setting->value) }}" 
                                                             alt="{{ $setting->label }}" 
                                                             class="w-16 h-16 object-cover rounded-lg">
                                                    @endif
                                                    <div>
                                                        <p class="text-xs text-muted-foreground dark:text-gray-300">Current {{ $setting->label }}</p>
                                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ basename($setting->value) }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                            <input type="file" 
                                                   name="file_{{ $setting->key }}" 
                                                   accept="image/*,.svg,.ico"
                                                   class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-black file:text-white hover:file:bg-gray-800">
                                            <p class="text-xs text-muted-foreground dark:text-gray-300">Upload new {{ strtolower($setting->label) }}</p>
                                        </div>
                                    @elseif($setting->isTextarea())
                                        <textarea name="settings[{{ $setting->key }}]" 
                                                  rows="3"
                                                  class="w-full px-3 py-2 border border-border rounded-lg focus:ring-black focus:border-black text-sm">{{ $setting->value }}</textarea>
                                    @else
                                        <input type="text" 
                                               name="settings[{{ $setting->key }}]" 
                                               value="{{ $setting->value }}"
                                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-black focus:border-black text-sm">
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Settings Preview -->
    <div class="py-6 border-t border-border dark:border-gray-700">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Settings Preview</h3>
                    <p class="text-xs text-muted-foreground mt-1">How your settings will appear on the frontend</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-muted-foreground mb-3">Site Information</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">Site Name:</span>
                                    <span class="ml-2 text-foreground">{{ $settings['general']->where('key', 'site_name')->first()?->value ?? 'Not set' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">Site URL:</span>
                                    <span class="ml-2 text-foreground">{{ $settings['general']->where('key', 'site_url')->first()?->value ?? 'Not set' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">Site Email:</span>
                                    <span class="ml-2 text-foreground">{{ $settings['general']->where('key', 'site_email')->first()?->value ?? 'Not set' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">Site Phone:</span>
                                    <span class="ml-2 text-foreground">{{ $settings['general']->where('key', 'site_phone')->first()?->value ?? 'Not set' }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-muted-foreground mb-3">Security Settings</h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">KYC Verification:</span>
                                    <span class="ml-2 {{ $settings['security']->where('key', 'enable_kyc')->first()?->value == '1' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $settings['security']->where('key', 'enable_kyc')->first()?->value == '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">Email Verification:</span>
                                    <span class="ml-2 {{ $settings['security']->where('key', 'enable_email_verification')->first()?->value == '1' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $settings['security']->where('key', 'enable_email_verification')->first()?->value == '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-muted-foreground dark:text-gray-300">Maintenance Mode:</span>
                                    <span class="ml-2 {{ $settings['general']->where('key', 'maintenance_mode')->first()?->value == '1' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $settings['general']->where('key', 'maintenance_mode')->first()?->value == '1' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
