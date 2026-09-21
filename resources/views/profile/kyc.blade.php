<x-user-layout>
    <x-slot name="header">
        {{ localize('ui.r2e.kyc.header', 'KYC Verification') }}
    </x-slot>

    <div class="ui-page max-w-6xl">
        <!-- Enhanced Header -->
        <div class="bg-gradient-to-br from-tesla-600 via-tesla-700 to-tesla-800 dark:from-tesla-700 dark:via-tesla-800 dark:to-tesla-900 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-card rounded-full -translate-y-16 translate-x-16"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-card rounded-full translate-y-8 -translate-x-8"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-4 lg:mb-0 lg:flex-1">
                        <h1 class="text-xl font-light mb-1">{{ localize('ui.r2e.kyc.header', 'KYC Verification') }}</h1>
                        <p class="text-tesla-100 dark:text-gray-300 text-sm">{{ localize('ui.r2e.kyc.hero_help', 'Complete your identity verification to access all features') }}</p>
                    </div>
                    
                    <!-- Enhanced Stats Card -->
                    <div class="bg-card bg-opacity-15 backdrop-blur-xl rounded-xl p-4 border border-white border-opacity-20 shadow-xl lg:w-64">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-tesla-100 dark:text-gray-300 mb-1">{{ localize('ui.r2e.kyc.verification_status', 'Verification Status') }}</p>
                                @if($kyc)
                                    <p class="text-lg font-light">{{ $kyc->status_label }}</p>
                                @else
                                    <p class="text-lg font-light">{{ localize('ui.r2e.kyc.not_submitted', 'Not Submitted') }}</p>
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
                        <h3 class="text-lg font-medium text-green-800 dark:text-green-200">{{ localize('ui.r2e.kyc.approved', 'KYC Verification Approved') }}</h3>
                        <p class="text-sm text-green-600 dark:text-green-300">{{ localize('ui.r2e.kyc.approved_help', 'Your identity has been verified successfully. You have full access to all platform features.') }}</p>
                        <p class="text-xs text-green-500 dark:text-green-400 mt-1">{{ localize('ui.r2e.kyc.verified_on', 'Verified on: :date', ['date' => $kyc->formatted_verified_at]) }}</p>
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
                        <h3 class="text-lg font-medium text-red-800 dark:text-red-200">{{ localize('ui.r2e.kyc.rejected', 'KYC Verification Rejected') }}</h3>
                        <p class="text-sm text-red-600 dark:text-red-300">{{ $kyc->rejection_reason }}</p>
                        <p class="text-xs text-red-500 dark:text-red-400 mt-1">{{ localize('ui.r2e.kyc.resubmit', 'Please update your documents and resubmit.') }}</p>
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
                        <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200">{{ localize('ui.r2e.kyc.pending', 'KYC Verification Pending') }}</h3>
                        <p class="text-sm text-yellow-600 dark:text-yellow-300">{{ localize('ui.r2e.kyc.pending_help', 'Your documents are under review. This process typically takes 24-48 hours.') }}</p>
                        <p class="text-xs text-yellow-500 dark:text-yellow-400 mt-1">{{ localize('ui.r2e.kyc.submitted_on', 'Submitted on: :date', ['date' => $kyc->formatted_submitted_at]) }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(!$kyc || ($kyc && $kyc->isRejected()))
        <!-- KYC Form -->
        <div class="ui-panel p-5 sm:p-6">
            <div class="mb-6">
                <h2 class="text-lg font-light text-foreground mb-1">{{ localize('ui.r2e.kyc.identity_verification', 'Identity Verification') }}</h2>
                <p class="text-sm text-muted-foreground dark:text-gray-300">{{ localize('ui.r2e.kyc.form_help', 'Please provide your identification documents for verification') }}</p>
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
                        <h3 class="text-md font-medium text-foreground">{{ localize('ui.r2e.kyc.personal_information', 'Personal Information') }}</h3>
                        
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.first_name', 'First Name') }}</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $kyc->first_name ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.last_name', 'Last Name') }}</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $kyc->last_name ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.common.date_of_birth', 'Date of birth') }}</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $kyc->date_of_birth ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="nationality" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.nationality', 'Nationality') }}</label>
                            <select id="nationality" name="nationality" required
                                    class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                                <option value="">{{ localize('ui.r2e.kyc.select_nationality', 'Select nationality') }}</option>
                                @foreach(($countries ?? []) as $c)
                                    <option value="{{ $c }}" {{ old('nationality', $kyc->nationality ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Document Information -->
                    <div class="space-y-4">
                        <h3 class="text-md font-medium text-foreground">{{ localize('ui.r2e.kyc.document_information', 'Document Information') }}</h3>
                        
                        <div>
                            <label for="document_type" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.document_type', 'Document Type') }}</label>
                            <select id="document_type" name="document_type" required
                                    class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                                <option value="">{{ localize('ui.r2e.kyc.select_document_type', 'Select document type') }}</option>
                                <option value="passport" {{ old('document_type', $kyc->document_type ?? '') == 'passport' ? 'selected' : '' }}>{{ localize('ui.r2e.kyc.passport', 'Passport') }}</option>
                                <option value="national_id" {{ old('document_type', $kyc->document_type ?? '') == 'national_id' ? 'selected' : '' }}>{{ localize('ui.r2e.kyc.national_id', 'National ID') }}</option>
                                <option value="drivers_license" {{ old('document_type', $kyc->document_type ?? '') == 'drivers_license' ? 'selected' : '' }}>{{ localize('ui.r2e.kyc.drivers_license', "Driver's License") }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="document_number" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.document_number', 'Document Number') }}</label>
                            <input type="text" id="document_number" name="document_number" value="{{ old('document_number', $kyc->document_number ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="document_expiry_date" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.expiry', 'Document Expiry Date') }}</label>
                            <input type="date" id="document_expiry_date" name="document_expiry_date" value="{{ old('document_expiry_date', $kyc->document_expiry_date ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.phone', 'Phone Number') }}</label>
                            <input type="tel" id="phone_number" name="phone_number" value="{{ old('phone_number', $kyc->phone_number ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="mt-6 space-y-4">
                    <h3 class="text-md font-medium text-foreground">{{ localize('ui.r2e.kyc.address_information', 'Address Information') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="address_line_1" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.address1', 'Address Line 1') }}</label>
                            <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $kyc->address_line_1 ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="address_line_2" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.address2', 'Address Line 2 (Optional)') }}</label>
                            <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $kyc->address_line_2 ?? '') }}"
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.city', 'City') }}</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $kyc->city ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="state_province" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.state_province', 'State/Province') }}</label>
                            <input type="text" id="state_province" name="state_province" value="{{ old('state_province', $kyc->state_province ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.kyc.postal', 'Postal Code') }}</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $kyc->postal_code ?? '') }}" required
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                        </div>

                        <div>
                            <label for="country" class="block text-sm font-medium text-foreground mb-1">{{ localize('ui.r2e.common.country', 'Country') }}</label>
                            <select id="country" name="country" required
                                    class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                                <option value="">{{ localize('ui.r2e.common.select_country', 'Select country') }}</option>
                                @foreach(($countries ?? []) as $c)
                                    <option value="{{ $c }}" {{ old('country', $kyc->country ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Document Upload -->
                <div class="mt-6 space-y-4">
                    <h3 class="text-md font-medium text-foreground">{{ localize('ui.r2e.kyc.document_upload', 'Document Upload') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="document_front" class="block text-sm font-medium text-foreground mb-1">
                                {{ localize('ui.r2e.kyc.document_front', 'Document Front') }} {{ $kyc ? localize('ui.r2e.kyc.update_suffix', '(Update)') : '' }}
                            </label>
                            <input type="file" id="document_front" name="document_front" accept="image/*" {{ $kyc ? '' : 'required' }}
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mt-1">{{ localize('ui.r2e.kyc.front_help', 'Upload the front of your ID document') }}</p>
                        </div>

                        <div>
                            <label for="document_back" class="block text-sm font-medium text-foreground mb-1">
                                {{ localize('ui.r2e.kyc.document_back', 'Document Back') }} {{ $kyc ? localize('ui.r2e.kyc.update_suffix', '(Update)') : '' }}
                            </label>
                            <input type="file" id="document_back" name="document_back" accept="image/*" {{ $kyc ? '' : 'required' }}
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mt-1">{{ localize('ui.r2e.kyc.back_help', 'Upload the back of your ID document') }}</p>
                        </div>

                        <div>
                            <label for="selfie" class="block text-sm font-medium text-foreground mb-1">
                                {{ localize('ui.r2e.kyc.selfie', 'Selfie') }} {{ $kyc ? localize('ui.r2e.kyc.update_suffix', '(Update)') : '' }}
                            </label>
                            <input type="file" id="selfie" name="selfie" accept="image/*" {{ $kyc ? '' : 'required' }}
                                   class="w-full px-3 py-2 border border-border dark:text-white rounded-lg focus:ring-2 focus:ring-ring dark:focus:ring-ring focus:border-transparent transition-colors duration-200">
                            <p class="text-xs text-muted-foreground dark:text-gray-300 mt-1">{{ localize('ui.r2e.kyc.selfie_help', 'Upload a clear photo of yourself') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6">
                    <button type="submit" class="w-full px-6 py-3 bg-foreground text-background font-medium rounded-lg hover:opacity-90 transition-colors duration-200">
                        {{ $kyc ? localize('ui.r2e.kyc.update_verification', 'Update KYC Verification') : localize('ui.r2e.kyc.submit_verification', 'Submit KYC Verification') }}
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    
</x-user-layout>
