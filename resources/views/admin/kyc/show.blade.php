<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight mr-4">
                    KYC Application Details
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.kyc.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-muted text-muted-foreground text-sm font-medium rounded-lg hover:bg-gray-200 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to KYC
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Application Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Application Information</h3>
                        </div>
                        
                        <div class="p-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Full Name</label>
                                    <p class="text-sm text-foreground">{{ $kyc->full_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Date of Birth</label>
                                    <p class="text-sm text-foreground">{{ $kyc->formatted_date_of_birth }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Nationality</label>
                                    <p class="text-sm text-foreground">{{ $kyc->nationality }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Document Type</label>
                                    <p class="text-sm text-foreground">{{ $kyc->document_type_label }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Document Number</label>
                                    <p class="text-sm text-foreground">{{ $kyc->document_number }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Document Expiry</label>
                                    <p class="text-sm text-foreground">{{ $kyc->formatted_document_expiry }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Address Information</h3>
                        </div>
                        
                        <div class="p-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Address Line 1</label>
                                    <p class="text-sm text-foreground">{{ $kyc->address_line_1 }}</p>
                                </div>
                                @if($kyc->address_line_2)
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Address Line 2</label>
                                    <p class="text-sm text-foreground">{{ $kyc->address_line_2 }}</p>
                                </div>
                                @endif
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">City</label>
                                    <p class="text-sm text-foreground">{{ $kyc->city }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">State/Province</label>
                                    <p class="text-sm text-foreground">{{ $kyc->state_province }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Postal Code</label>
                                    <p class="text-sm text-foreground">{{ $kyc->postal_code }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Country</label>
                                    <p class="text-sm text-foreground">{{ $kyc->country }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Phone Number</label>
                                    <p class="text-sm text-foreground">{{ $kyc->phone_number }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Images -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Document Images</h3>
                        </div>
                        
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @if($kyc->document_front_path)
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Document Front</label>
                                    <img src="{{ asset('storage/' . $kyc->document_front_path) }}" 
                                         alt="Document Front" 
                                         class="w-full h-32 object-cover rounded-lg border border-border dark:border-gray-700">
                                </div>
                                @endif
                                
                                @if($kyc->document_back_path)
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Document Back</label>
                                    <img src="{{ asset('storage/' . $kyc->document_back_path) }}" 
                                         alt="Document Back" 
                                         class="w-full h-32 object-cover rounded-lg border border-border dark:border-gray-700">
                                </div>
                                @endif
                                
                                @if($kyc->selfie_path)
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-2">Selfie</label>
                                    <img src="{{ asset('storage/' . $kyc->selfie_path) }}" 
                                         alt="Selfie" 
                                         class="w-full h-32 object-cover rounded-lg border border-border dark:border-gray-700">
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Information -->
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">User Information</h3>
                        </div>
                        
                        <div class="p-4 space-y-4">
                            <div class="flex items-center space-x-3">
                                @if($kyc->user->profile_image)
                                    <img src="{{ asset('storage/' . $kyc->user->profile_image) }}" 
                                         alt="{{ $kyc->user->name }}" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">{{ strtoupper(substr($kyc->user->name, 0, 2)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-foreground">{{ $kyc->user->name }}</h4>
                                    <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $kyc->user->email }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Application Status</label>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $kyc->status_color === 'green' ? 'bg-green-100 text-green-800' : ($kyc->status_color === 'red' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ $kyc->status_label }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Submitted</label>
                                <p class="text-sm text-foreground">{{ $kyc->formatted_submitted_at }}</p>
                            </div>

                            @if($kyc->verified_at)
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Verified</label>
                                <p class="text-sm text-foreground">{{ $kyc->formatted_verified_at }}</p>
                            </div>
                            @endif

                            @if($kyc->rejection_reason)
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Rejection Reason</label>
                                <p class="text-sm text-red-600">{{ $kyc->rejection_reason }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    @if($kyc->status === 'pending')
                    <div class="bg-card border border-border overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                            <h3 class="text-base font-medium text-foreground">Actions</h3>
                        </div>
                        
                        <div class="p-4 space-y-3">
                            <form method="POST" action="{{ route('admin.kyc.approve', $kyc) }}">
                                @csrf
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                    Approve Application
                                </button>
                            </form>
                            
                            <button onclick="openRejectModal()" 
                                    class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                Reject Application
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Danger Zone -->
                    <div class="bg-card border border-red-300 dark:border-red-900/60 overflow-hidden rounded-lg">
                        <div class="px-4 py-3 border-b border-red-200 bg-red-50">
                            <h3 class="text-base font-medium text-red-800">Danger Zone</h3>
                        </div>
                        
                        <div class="p-4">
                            <form method="POST" action="{{ route('admin.kyc.destroy', $kyc) }}" onsubmit="return confirm('Are you sure you want to delete this KYC application? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-red-100 text-red-700 text-sm font-medium rounded-lg hover:bg-red-200 transition-colors">
                                    Delete Application
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border border-border w-96 max-w-[calc(100vw-2rem)] shadow-lg rounded-xl bg-card text-card-foreground">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-4">Reject KYC Application</h3>
                <p class="text-sm text-muted-foreground mb-4">Are you sure you want to reject the KYC application for {{ $kyc->user->name }}?</p>
                
                <form method="POST" action="{{ route('admin.kyc.reject', $kyc) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="block text-xs font-medium text-muted-foreground mb-2">Rejection Reason *</label>
                        <textarea id="rejection_reason" 
                                  name="rejection_reason" 
                                  rows="3" 
                                  class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-colors duration-200"
                                  placeholder="Please provide a reason for rejection..."
                                  required></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeRejectModal()" 
                                class="px-4 py-2 text-sm font-medium text-muted-foreground bg-muted dark:bg-dark-muted rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            Reject Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</x-admin-layout>
