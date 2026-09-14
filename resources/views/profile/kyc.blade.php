<x-user-layout>
    <x-slot name="header">
        KYC Verification
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-white rounded-full translate-y-8 -translate-x-8"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">KYC Verification</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">Complete your identity verification to access all features</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-white bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">Verification Status</p>
                                @if($kyc)
                                    <p class="text-lg font-light">{{ $kyc->status_label }}</p>
                                @else
                                    <p class="text-lg font-light">Not Submitted</p>
                                @endif
                            </div>
                            <div class="w-10 h-10 flex items-center justify-center">
                                <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($kyc && $kyc->isApproved())
            <!-- KYC Approved -->
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl p-6 mb-6 transition-colors duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center mr-4">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600 dark:text-green-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-green-800 dark:text-green-200">KYC Verification Approved</h3>
                        <p class="text-sm text-green-600 dark:text-green-300">Your identity has been verified successfully. You have full access to all platform features.</p>
                        <p class="text-xs text-green-500 dark:text-green-400 mt-1">Verified on: {{ $kyc->formatted_verified_at }}</p>
                    </div>
                </div>
            </div>
        @elseif($kyc && $kyc->isRejected())
            <!-- KYC Rejected -->
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl p-6 mb-6 transition-colors duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center mr-4">
                        <i data-lucide="x-circle" class="w-6 h-6 text-red-600 dark:text-red-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-red-800 dark:text-red-200">KYC Verification Rejected</h3>
                        <p class="text-sm text-red-600 dark:text-red-300">{{ $kyc->rejection_reason }}</p>
                        <p class="text-xs text-red-500 dark:text-red-400 mt-1">Please update your documents and resubmit.</p>
                    </div>
                </div>
            </div>
        @elseif($kyc && $kyc->isPending())
            <!-- KYC Pending -->
            <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-xl p-6 mb-6 transition-colors duration-200">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center mr-4">
                        <i data-lucide="clock" class="w-6 h-6 text-yellow-600 dark:text-yellow-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200">KYC Verification Pending</h3>
                        <p class="text-sm text-yellow-600 dark:text-yellow-300">Your documents are under review. This process typically takes 24-48 hours.</p>
                        <p class="text-xs text-yellow-500 dark:text-yellow-400 mt-1">Submitted on: {{ $kyc->formatted_submitted_at }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!$kyc || ($kyc && $kyc->isRejected()))
        <!-- KYC Form -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-border">
            <div class="mb-6">
                <h2 class="text-lg font-light text-black dark:text-white mb-1">Identity Verification</h2>
                <p class="text-sm text-gray-500 dark:text-gray-300">Please provide your identification documents for verification</p>
            </div>

            @if($kyc)
                <form action="{{ route('profile.kyc.update', $kyc) }}" method="POST" enctype="multipart/form-data">
                    @method('PATCH')
            @else
                <form action="{{ route('profile.kyc.store') }}" method="POST" enctype="multipart/form-data">
            @endif
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h3 class="text-md font-medium text-black dark:text-white">Personal Information</h3>
                        
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-foreground mb-1">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $kyc->first_name ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-foreground mb-1">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $kyc->last_name ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-foreground mb-1">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $kyc->date_of_birth ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="nationality" class="block text-sm font-medium text-foreground mb-1">Nationality</label>
                            <select id="nationality" name="nationality" required
                                    class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                                <option value="">Select nationality</option>
                                @foreach(($countries ?? []) as $c)
                                    <option value="{{ $c }}" {{ old('nationality', $kyc->nationality ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Document Information -->
                    <div class="space-y-4">
                        <h3 class="text-md font-medium text-black dark:text-white">Document Information</h3>
                        
                        <div>
                            <label for="document_type" class="block text-sm font-medium text-foreground mb-1">Document Type</label>
                            <select id="document_type" name="document_type" required
                                    class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                                <option value="">Select document type</option>
                                <option value="passport" {{ old('document_type', $kyc->document_type ?? '') == 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="national_id" {{ old('document_type', $kyc->document_type ?? '') == 'national_id' ? 'selected' : '' }}>National ID</option>
                                <option value="drivers_license" {{ old('document_type', $kyc->document_type ?? '') == 'drivers_license' ? 'selected' : '' }}>Driver's License</option>
                            </select>
                        </div>

                        <div>
                            <label for="document_number" class="block text-sm font-medium text-foreground mb-1">Document Number</label>
                            <input type="text" id="document_number" name="document_number" value="{{ old('document_number', $kyc->document_number ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="document_expiry_date" class="block text-sm font-medium text-foreground mb-1">Document Expiry Date</label>
                            <input type="date" id="document_expiry_date" name="document_expiry_date" value="{{ old('document_expiry_date', $kyc->document_expiry_date ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-foreground mb-1">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" value="{{ old('phone_number', $kyc->phone_number ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="mt-6 space-y-4">
                    <h3 class="text-md font-medium text-black dark:text-white">Address Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="address_line_1" class="block text-sm font-medium text-foreground mb-1">Address Line 1</label>
                            <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $kyc->address_line_1 ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="address_line_2" class="block text-sm font-medium text-foreground mb-1">Address Line 2 (Optional)</label>
                            <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $kyc->address_line_2 ?? '') }}"
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-foreground mb-1">City</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $kyc->city ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="state_province" class="block text-sm font-medium text-foreground mb-1">State/Province</label>
                            <input type="text" id="state_province" name="state_province" value="{{ old('state_province', $kyc->state_province ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-foreground mb-1">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $kyc->postal_code ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="country" class="block text-sm font-medium text-foreground mb-1">Country</label>
                            <select id="country" name="country" required
                                    class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                                <option value="">Select country</option>
                                @foreach(($countries ?? []) as $c)
                                    <option value="{{ $c }}" {{ old('country', $kyc->country ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Document Upload -->
                <div class="mt-6 space-y-4">
                    <h3 class="text-md font-medium text-black dark:text-white">Document Upload</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="document_front" class="block text-sm font-medium text-foreground mb-1">
                                Document Front {{ $kyc ? '(Update)' : '' }}
                            </label>
                            <input type="file" id="document_front" name="document_front" accept="image/*" {{ $kyc ? '' : 'required' }}
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                            <p class="text-xs text-gray-500 dark:text-gray-300 mt-1">Upload the front of your ID document</p>
                        </div>

                        <div>
                            <label for="document_back" class="block text-sm font-medium text-foreground mb-1">
                                Document Back {{ $kyc ? '(Update)' : '' }}
                            </label>
                            <input type="file" id="document_back" name="document_back" accept="image/*" {{ $kyc ? '' : 'required' }}
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                            <p class="text-xs text-gray-500 dark:text-gray-300 mt-1">Upload the back of your ID document</p>
                        </div>

                        <div>
                            <label for="selfie" class="block text-sm font-medium text-foreground mb-1">
                                Selfie {{ $kyc ? '(Update)' : '' }}
                            </label>
                            <input type="file" id="selfie" name="selfie" accept="image/*" {{ $kyc ? '' : 'required' }}
                                   class="w-full px-3 py-2 border border-border dark:bg-dark-muted dark:text-white rounded-lg focus:ring-2 focus:ring-black dark:focus:ring-tesla-400 focus:border-transparent transition-colors duration-200">
                            <p class="text-xs text-gray-500 dark:text-gray-300 mt-1">Upload a clear photo of yourself</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6">
                    <button type="submit" class="w-full px-6 py-3 bg-black dark:bg-white text-white dark:text-gray-900 font-medium rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors duration-200">
                        {{ $kyc ? 'Update KYC Verification' : 'Submit KYC Verification' }}
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    
</x-user-layout>
