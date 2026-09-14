<x-user-layout>
    <x-slot name="header">
        Support
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Hero / Intro -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white rounded-full -translate-y-24 translate-x-24"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
            </div>
            <div class="relative z-10">
                <h1 class="text-xl font-light mb-1">How can we help?</h1>
                <p class="text-tesla-100 dark:text-gray-300 text-sm">Get in touch with our team for assistance with your account, wallet, investments, or technical issues.</p>
            </div>
        </div>

        <!-- Support Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-tesla-50 dark:bg-tesla-800 rounded-2xl p-6 border border-border shadow-sm">
                <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Your Name</label>
                            <input type="text" value="{{ auth()->user()->name }}" disabled class="w-full rounded-lg border-border text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" value="{{ auth()->user()->email }}" disabled class="w-full rounded-lg border-border text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                            <select name="category" class="w-full rounded-lg border-border text-sm" required>
                                <option value="" disabled selected>Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" class="w-full rounded-lg border-border text-sm" placeholder="Brief summary" required />
                            @error('subject')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Message</label>
                        <textarea name="message" rows="6" class="w-full rounded-lg border-border text-sm" placeholder="Describe the issue or question" required>{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Attachment (optional)</label>
                        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm" />
                        <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, PDF. Max 5 MB.</p>
                        @error('attachment')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                            <i data-lucide="send" class="w-3 h-3 mr-2"></i>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>

            <div class="lg:col-span-1 space-y-4">
                <div class="bg-tesla-50 dark:bg-tesla-800 rounded-2xl p-6 border border-border shadow-sm">
                    <h3 class="text-sm font-medium text-black dark:text-white mb-2">Quick Help</h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                        <li class="flex items-center"><i data-lucide="shield-check" class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-300"></i> KYC verification status</li>
                        <li class="flex items-center"><i data-lucide="wallet" class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-300"></i> Wallet deposits & withdrawals</li>
                        <li class="flex items-center"><i data-lucide="trending-up" class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-300"></i> Investments & stocks</li>
                        <li class="flex items-center"><i data-lucide="settings" class="w-4 h-4 mr-2 text-gray-400 dark:text-gray-300"></i> Technical troubleshooting</li>
                    </ul>
                </div>

                <div class="bg-tesla-50 dark:bg-tesla-800 rounded-2xl p-6 border border-border shadow-sm">
                    <h3 class="text-sm font-medium text-black dark:text-white mb-2">Response Times</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-300">We typically respond within 24 hours on weekdays.</p>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>


