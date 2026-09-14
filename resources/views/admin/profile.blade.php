<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    Admin Profile Settings
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Admin Profile Information -->
            <div class="bg-card border border-border rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Administrator Profile</h3>
                    <p class="text-xs text-muted-foreground mt-1">Manage your administrator account information and security settings.</p>
                </div>

                <form method="post" action="{{ route('admin.profile.update') }}" class="p-4 space-y-4">
                    @csrf
                    @method('patch')

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-medium text-muted-foreground mb-2">Full Name</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
                               required 
                               autofocus 
                               autocomplete="name" 
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors duration-200 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-medium text-muted-foreground mb-2">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}" 
                               required 
                               autocomplete="username" 
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors duration-200 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role Display -->
                    <div>
                        <label class="block text-xs font-medium text-muted-foreground mb-2">Role</label>
                        <div class="w-full px-3 py-2 bg-muted/40 border border-border rounded-lg text-muted-foreground text-sm">
                            Administrator
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground dark:text-gray-300">Your role cannot be changed from this interface.</p>
                    </div>

                    <!-- Save Button -->
                    <div class="flex items-center gap-4 pt-2">
                        <button type="submit" class="bg-black hover:bg-gray-800 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200">
                            Update Profile
                        </button>

                        @if (session('status') === 'profile-updated')
                            <p class="text-xs text-green-600">Profile updated successfully.</p>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="bg-card border border-border rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Security Settings</h3>
                    <p class="text-xs text-muted-foreground mt-1">Manage your password and security preferences.</p>
                </div>

                <form method="post" action="{{ route('admin.password.update') }}" class="p-4 space-y-4">
                    @csrf
                    @method('put')

                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-xs font-medium text-muted-foreground mb-2">Current Password</label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               autocomplete="current-password" 
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors duration-200 @error('current_password', 'updatePassword') border-red-500 @enderror">
                        @error('current_password', 'updatePassword')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-xs font-medium text-muted-foreground mb-2">New Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               autocomplete="new-password" 
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors duration-200 @error('password', 'updatePassword') border-red-500 @enderror">
                        @error('password', 'updatePassword')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-muted-foreground dark:text-gray-300">Password must be at least 8 characters long.</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-muted-foreground mb-2">Confirm New Password</label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               autocomplete="new-password" 
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors duration-200 @error('password_confirmation', 'updatePassword') border-red-500 @enderror">
                        @error('password_confirmation', 'updatePassword')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Save Button -->
                    <div class="flex items-center gap-4 pt-2">
                        <button type="submit" class="bg-black hover:bg-gray-800 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors duration-200">
                            Update Password
                        </button>

                        @if (session('status') === 'password-updated')
                            <p class="text-xs text-green-600">Password updated successfully.</p>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Account Information -->
            <div class="bg-card border border-border rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Account Information</h3>
                    <p class="text-xs text-muted-foreground mt-1">View your account details and activity.</p>
                </div>

                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-xs font-medium text-muted-foreground mb-2">Account Created</h4>
                            <p class="text-sm text-foreground">{{ $user->created_at->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <h4 class="text-xs font-medium text-muted-foreground mb-2">Last Updated</h4>
                            <p class="text-sm text-foreground">{{ $user->updated_at->format('M d, Y g:i A') }}</p>
                        </div>
                        <div>
                            <h4 class="text-xs font-medium text-muted-foreground mb-2">Email Status</h4>
                            @if($user->email_verified_at)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                    Verified
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                    Not Verified
                                </span>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-xs font-medium text-muted-foreground mb-2">Account Status</h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout> 
