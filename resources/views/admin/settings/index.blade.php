<x-admin-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-light text-lg text-foreground leading-tight">Site Settings</h2>
            <p class="mt-1 text-xs text-muted-foreground">Brand, mail, application and operational configuration.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <details class="mb-6 overflow-hidden rounded-lg border border-border bg-card">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50">
                    <span class="flex items-center gap-2"><svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg> System maintenance tools</span>
                    <span class="text-xs font-normal text-muted-foreground">Open only when needed</span>
                </summary>
                <div class="border-t border-border bg-muted/20 p-4">
                    <p class="mb-3 text-xs text-muted-foreground">These are server maintenance actions, not everyday dashboard controls.</p>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.settings.fix-storage') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Fix Storage</button></form>
                        <form method="POST" action="{{ route('admin.settings.storage-link') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Storage Link</button></form>
                        <form method="POST" action="{{ route('admin.settings.clear-cache') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Clear Cache</button></form>
                        <form method="POST" action="{{ route('admin.settings.clear-config') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Clear Config</button></form>
                        <form method="POST" action="{{ route('admin.settings.clear-views') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Clear Views</button></form>
                        <form method="POST" action="{{ route('admin.settings.clear-routes') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Clear Routes</button></form>
                        <form method="POST" action="{{ route('admin.settings.optimize-clear') }}">@csrf<button type="submit" class="ui-button-secondary !py-2 !text-xs">Clear All</button></form>
                        <form method="POST" action="{{ route('admin.settings.reset-defaults') }}">@csrf<button type="submit" onclick="return confirm('Reset all settings to defaults? This cannot be undone.')" class="inline-flex items-center justify-center rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs font-medium text-destructive transition hover:bg-destructive/15">Reset Defaults</button></form>
                    </div>
                </div>
            </details>
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
                                                   class="w-4 h-4 text-foreground border-border rounded focus:ring-ring focus:ring-2">
                                            <span class="ml-2 text-sm text-muted-foreground dark:text-gray-300">Enable {{ $setting->label }}</span>
                                        </div>
                                    @elseif($setting->isImage())
                                        <div class="space-y-3">
                                            @if($setting->value)
                                                <div class="flex items-center space-x-3">
                                                    @if(in_array(pathinfo($setting->value, PATHINFO_EXTENSION), ['svg', 'ico']))
                                                        <div class="w-16 h-16 bg-muted rounded-lg flex items-center justify-center">
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
                                    @elseif($setting->isPassword())
                                        <input type="password"
                                               name="settings[{{ $setting->key }}]"
                                               value=""
                                               autocomplete="new-password"
                                               placeholder="{{ $setting->value ? '•••••••• (leave blank to keep current)' : 'Enter secret' }}"
                                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-ring focus:border-black text-sm">
                                    @elseif($setting->isTextarea())
                                        <textarea name="settings[{{ $setting->key }}]" 
                                                  rows="3"
                                                  class="w-full px-3 py-2 border border-border rounded-lg focus:ring-ring focus:border-black text-sm">{{ $setting->value }}</textarea>
                                    @else
                                        <input type="text" 
                                               name="settings[{{ $setting->key }}]" 
                                               value="{{ $setting->value }}"
                                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-ring focus:border-black text-sm">
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
                    <button type="submit" class="px-6 py-3 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:opacity-90 transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mail Delivery Test -->
    <div class="pb-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-card border border-border rounded-lg p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h3 class="text-base font-medium text-foreground">Test Mail Delivery</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Save the mail settings above first, then send a real SMTP test message.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.test-mail') }}" class="flex w-full max-w-xl flex-col gap-2 sm:flex-row">
                        @csrf
                        <input type="email" name="test_email" required placeholder="you@example.com"
                               class="min-w-0 flex-1 px-3 py-2 border border-border rounded-lg focus:ring-ring focus:border-black text-sm">
                        <button type="submit"
                                class="px-4 py-2 bg-tesla-600 text-white text-sm font-medium rounded-lg hover:bg-tesla-700 transition-colors">
                            Send Test Email
                        </button>
                    </form>
                </div>
            </div>
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
