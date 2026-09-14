<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    {{ __('Create Email Template') }}
                </h2>
                <p class="text-xs text-muted-foreground mt-1">Create a new email template for sending notifications to users</p>
            </div>
            <a href="{{ route('admin.emails.index') }}" 
               class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Templates
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('admin.emails.templates.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Template Form -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Basic Information -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Template Information</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label for="name" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Template Name *
                                    </label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black"
                                           placeholder="e.g., Welcome Email, Purchase Confirmation">
                                    @error('name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="type" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Template Type *
                                    </label>
                                    <select name="type" id="type" required
                                            class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black">
                                        <option value="">Select type...</option>
                                        <option value="general" {{ old('type') === 'general' ? 'selected' : '' }}>General</option>
                                        <option value="purchase" {{ old('type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                                        <option value="notification" {{ old('type') === 'notification' ? 'selected' : '' }}>Notification</option>
                                        <option value="welcome" {{ old('type') === 'welcome' ? 'selected' : '' }}>Welcome</option>
                                        <option value="reminder" {{ old('type') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                                    </select>
                                    @error('type')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="subject" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Email Subject *
                                    </label>
                                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black"
                                           placeholder="e.g., Welcome to Tesla Drives, @{{user_name}}!">
                                    @error('subject')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Email Content -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Email Content</h3>
                            
                            <div>
                                <label for="content" class="block text-xs font-medium text-muted-foreground mb-2">
                                    Email Content *
                                </label>
                                <textarea name="content" id="content" rows="12" required
                                          class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black"
                                          placeholder="Write your email content here. You can use variables like @{{user_name}}, @{{user_email}}, etc.">{{ old('content') }}</textarea>
                                @error('content')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-muted-foreground dark:text-gray-300">
                                    Use double curly braces for variables: @{{variable_name}}
                                </p>
                            </div>
                        </div>

                        <!-- Template Variables -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Custom Variables</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label for="variables" class="block text-xs font-medium text-muted-foreground mb-2">
                                        Variable Names (comma-separated)
                                    </label>
                                    <input type="text" name="variables" id="variables" value="{{ old('variables') }}"
                                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black"
                                           placeholder="e.g., user_name, user_email, order_number">
                                    <p class="mt-1 text-xs text-muted-foreground dark:text-gray-300">
                                        List variable names that can be used in this template
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Settings -->
                    <div class="space-y-4">
                        <!-- Template Status -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Template Settings</h3>
                            
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                           class="text-foreground focus:ring-black">
                                    <span class="ml-2 text-sm text-foreground">Active template</span>
                                </label>
                                
                                <div class="bg-tesla-50 border border-tesla-200 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-tesla-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="text-xs text-tesla-800">
                                            <strong>Tip:</strong> Only active templates can be used when sending emails to users.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variable Reference -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <h3 class="text-base font-medium text-foreground dark:text-white mb-3">Available Variables</h3>
                            
                            <div class="space-y-2">
                                <div class="text-xs">
                                    <strong class="text-foreground">Built-in Variables:</strong>
                                    <ul class="mt-1 text-muted-foreground space-y-1">
                                        <li>• <code>@{{user_name}}</code> - User's full name</li>
                                        <li>• <code>@{{user_email}}</code> - User's email address</li>
                                        <li>• <code>@{{user_id}}</code> - User's ID</li>
                                        <li>• <code>@{{site_name}}</code> - Website name</li>
                                        <li>• <code>@{{current_date}}</code> - Current date</li>
                                    </ul>
                                </div>
                                
                                <div class="text-xs">
                                    <strong class="text-foreground">Custom Variables:</strong>
                                    <p class="text-muted-foreground mt-1">Add your own variables in the field above and use them in your template.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="bg-card border border-border p-4 rounded-lg">
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-black dark:bg-card text-white dark:text-foreground text-sm font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-muted transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Create Template
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout> 
