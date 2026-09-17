<x-user-layout>
    <x-slot name="header">
        Profile Settings
    </x-slot>

    <div class="ui-page max-w-6xl">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-card rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-card rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">Profile Settings</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Manage your account information and preferences</p>
                    </div>
                    
                    <!-- Profile Image Preview -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Profile Image</p>
                                <div class="w-16 h-16 bg-gradient-to-br from-tesla-400 to-tesla-600 rounded-xl flex items-center justify-center">
                                    @if(auth()->user()->profile_image)
                                        <img src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="w-16 h-16 rounded-xl object-cover">
                                    @else
                                        <span class="text-white font-medium text-lg">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="user" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <div class="ui-panel p-5 sm:p-6">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('patch')

                <!-- Personal Information -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Personal Information</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Update your basic account details</p>
                        </div>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="user" class="w-4 h-4 text-tesla-600"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-foreground mb-2">Full Name</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-foreground mb-2">Email Address</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200"
                                   required>
                            @error('email')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="country" class="block text-sm font-medium text-foreground mb-2">Country</label>
                            <select id="country" 
                                    name="country" 
                                    class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                                <option value="">Select Country</option>
                            </select>
                            @error('country')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-foreground mb-2">Preferred Currency</label>
                            <select id="currency" 
                                    name="currency" 
                                    class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                                <option value="USD" {{ old('currency', $user->currency) == 'USD' ? 'selected' : '' }}>USD - US Dollar ($)</option>
                                <option value="EUR" {{ old('currency', $user->currency) == 'EUR' ? 'selected' : '' }}>EUR - Euro (€)</option>
                                <option value="GBP" {{ old('currency', $user->currency) == 'GBP' ? 'selected' : '' }}>GBP - British Pound (£)</option>
                                <option value="JPY" {{ old('currency', $user->currency) == 'JPY' ? 'selected' : '' }}>JPY - Japanese Yen (¥)</option>
                                <option value="AUD" {{ old('currency', $user->currency) == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar (A$)</option>
                                <option value="CAD" {{ old('currency', $user->currency) == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar (C$)</option>
                                <option value="CHF" {{ old('currency', $user->currency) == 'CHF' ? 'selected' : '' }}>CHF - Swiss Franc (CHF)</option>
                                <option value="CNY" {{ old('currency', $user->currency) == 'CNY' ? 'selected' : '' }}>CNY - Chinese Yuan (¥)</option>
                                <option value="INR" {{ old('currency', $user->currency) == 'INR' ? 'selected' : '' }}>INR - Indian Rupee (₹)</option>
                                <option value="KRW" {{ old('currency', $user->currency) == 'KRW' ? 'selected' : '' }}>KRW - South Korean Won (₩)</option>
                                <option value="MXN" {{ old('currency', $user->currency) == 'MXN' ? 'selected' : '' }}>MXN - Mexican Peso ($)</option>
                                <option value="BRL" {{ old('currency', $user->currency) == 'BRL' ? 'selected' : '' }}>BRL - Brazilian Real (R$)</option>
                                <option value="ZAR" {{ old('currency', $user->currency) == 'ZAR' ? 'selected' : '' }}>ZAR - South African Rand (R)</option>
                                <option value="RUB" {{ old('currency', $user->currency) == 'RUB' ? 'selected' : '' }}>RUB - Russian Ruble (₽)</option>
                                <option value="SEK" {{ old('currency', $user->currency) == 'SEK' ? 'selected' : '' }}>SEK - Swedish Krona (kr)</option>
                                <option value="NOK" {{ old('currency', $user->currency) == 'NOK' ? 'selected' : '' }}>NOK - Norwegian Krone (kr)</option>
                                <option value="DKK" {{ old('currency', $user->currency) == 'DKK' ? 'selected' : '' }}>DKK - Danish Krone (kr)</option>
                                <option value="SGD" {{ old('currency', $user->currency) == 'SGD' ? 'selected' : '' }}>SGD - Singapore Dollar (S$)</option>
                                <option value="HKD" {{ old('currency', $user->currency) == 'HKD' ? 'selected' : '' }}>HKD - Hong Kong Dollar (HK$)</option>
                                <option value="NZD" {{ old('currency', $user->currency) == 'NZD' ? 'selected' : '' }}>NZD - New Zealand Dollar (NZ$)</option>
                                <option value="TRY" {{ old('currency', $user->currency) == 'TRY' ? 'selected' : '' }}>TRY - Turkish Lira (₺)</option>
                                <option value="PLN" {{ old('currency', $user->currency) == 'PLN' ? 'selected' : '' }}>PLN - Polish Zloty (zł)</option>
                                <option value="THB" {{ old('currency', $user->currency) == 'THB' ? 'selected' : '' }}>THB - Thai Baht (฿)</option>
                                <option value="IDR" {{ old('currency', $user->currency) == 'IDR' ? 'selected' : '' }}>IDR - Indonesian Rupiah (Rp)</option>
                                <option value="MYR" {{ old('currency', $user->currency) == 'MYR' ? 'selected' : '' }}>MYR - Malaysian Ringgit (RM)</option>
                                <option value="PHP" {{ old('currency', $user->currency) == 'PHP' ? 'selected' : '' }}>PHP - Philippine Peso (₱)</option>
                                <option value="CZK" {{ old('currency', $user->currency) == 'CZK' ? 'selected' : '' }}>CZK - Czech Koruna (Kč)</option>
                                <option value="ILS" {{ old('currency', $user->currency) == 'ILS' ? 'selected' : '' }}>ILS - Israeli Shekel (₪)</option>
                                <option value="CLP" {{ old('currency', $user->currency) == 'CLP' ? 'selected' : '' }}>CLP - Chilean Peso ($)</option>
                                <option value="AED" {{ old('currency', $user->currency) == 'AED' ? 'selected' : '' }}>AED - UAE Dirham (د.إ)</option>
                            </select>
                            @error('currency')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Profile Image -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Profile Image</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Upload a new profile picture</p>
                        </div>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="image" class="w-4 h-4 text-purple-600"></i>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="profile_image" class="block text-sm font-medium text-foreground mb-2">Profile Image</label>
                            <div class="flex items-center space-x-4">
                                <div class="w-20 h-20 bg-gradient-to-br from-tesla-400 to-tesla-600 rounded-xl flex items-center justify-center overflow-hidden">
                                    @if(auth()->user()->profile_image)
                                        <img id="image-preview" src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="w-20 h-20 object-cover">
                                    @else
                                        <span id="image-preview-text" class="text-white font-medium text-xl">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" 
                                           id="profile_image" 
                                           name="profile_image" 
                                           accept="image/*"
                                           class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200"
                                           onchange="previewImage(this)">
                                    <p class="text-xs text-muted-foreground dark:text-gray-300 mt-1">JPG, PNG or GIF. Max 2MB.</p>
                                    @error('profile_image')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-light text-foreground mb-1">Security Settings</h3>
                            <p class="text-xs text-muted-foreground dark:text-gray-300">Update your password</p>
                        </div>
                        <div class="w-8 h-8 flex items-center justify-center">
                            <i data-lucide="shield" class="w-4 h-4 text-green-600"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-foreground mb-2">Current Password</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                            @error('current_password')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-foreground mb-2">New Password</label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                            @error('password')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-foreground mb-2">Confirm New Password</label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               class="w-full px-4 py-3 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring focus:border-transparent transition-colors duration-200">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-6 border-t border-border">
                    <div>
                        <p class="text-sm text-muted-foreground">Last updated: {{ $user->updated_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <button type="submit" 
                            class="px-6 py-3 bg-foreground text-background font-medium rounded-lg hover:opacity-90 transition-colors duration-200 flex items-center">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const previewText = document.getElementById('image-preview-text');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        // Create new image element
                        const img = document.createElement('img');
                        img.id = 'image-preview';
                        img.src = e.target.result;
                        img.className = 'w-20 h-20 object-cover';
                        img.alt = 'Profile preview';
                        
                        // Replace text with image
                        if (previewText) {
                            previewText.parentNode.replaceChild(img, previewText);
                        }
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                // Submit delete form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("profile.destroy") }}';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Load countries from JSON file
        fetch('/countries.json')
            .then(response => response.json())
            .then(countries => {
                const countrySelect = document.getElementById('country');
                const currentCountry = '{{ old("country", $user->country) }}';
                
                countries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = country.name;
                    if (country.code === currentCountry) {
                        option.selected = true;
                    }
                    countrySelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading countries:', error));
    </script>
</x-user-layout>
