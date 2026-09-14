<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    {{ __('Edit User') }}
                </h2>
            </div>
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                        <h3 class="text-base font-medium text-foreground">User Information</h3>
                        <p class="text-xs text-muted-foreground mt-1">Update the details for this user account</p>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- User Name -->
                        <div>
                            <label for="name" class="block text-xs font-medium text-muted-foreground mb-2">
                                Full Name *
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all duration-200 @error('name') border-red-500 @enderror"
                                   placeholder="Enter user's full name"
                                   required>
                            @error('name')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-medium text-muted-foreground mb-2">
                                Email Address *
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all duration-200 @error('email') border-red-500 @enderror"
                                   placeholder="Enter email address"
                                   required>
                            @error('email')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email Verification Status -->
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-2">
                                Email Verification Status
                            </label>
                            @if($user->email_verified_at)
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                        Verified
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-gray-300">Verified on {{ $user->email_verified_at->format('M d, Y') }}</span>
                                </div>
                            @else
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                        Not Verified
                                    </span>
                                    <span class="text-xs text-muted-foreground dark:text-gray-300">User needs to verify their email</span>
                                </div>
                            @endif
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-medium text-muted-foreground mb-2">
                                New Password
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all duration-200 @error('password') border-red-500 @enderror"
                                   placeholder="Leave blank to keep current password">
                            @error('password')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-muted-foreground mt-1">
                                Leave blank to keep the current password. If changing, password must be at least 8 characters long.
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-medium text-muted-foreground mb-2">
                                Confirm New Password
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation"
                                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all duration-200"
                                   placeholder="Confirm new password if changing">
                        </div>

                        <!-- User Type -->
                        <div>
                            <label class="block text-xs font-medium text-muted-foreground mb-3">
                                User Type
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="user_type" 
                                           value="user" 
                                           {{ old('user_type', $user->is_admin ? 'admin' : 'user') === 'user' ? 'checked' : '' }}
                                           class="w-4 h-4 text-foreground focus:ring-black border-border">
                                    <span class="ml-2 text-sm text-muted-foreground">Regular User</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="user_type" 
                                           value="admin" 
                                           {{ old('user_type', $user->is_admin ? 'admin' : 'user') === 'admin' ? 'checked' : '' }}
                                           class="w-4 h-4 text-foreground focus:ring-black border-border">
                                    <span class="ml-2 text-sm text-muted-foreground">Administrator</span>
                                </label>
                            </div>
                        </div>

                        <!-- Admin Role -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="is_admin" 
                                       value="1" 
                                       {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                                       class="w-4 h-4 text-foreground focus:ring-black border-border rounded">
                                <span class="ml-2 text-sm text-muted-foreground">Grant admin privileges</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="px-4 py-3 bg-muted/40 border-t border-border dark:border-gray-700 flex justify-end space-x-3">
                        <a href="{{ route('admin.users.show', $user) }}" 
                           class="ui-button-secondary">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-colors">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout> 
