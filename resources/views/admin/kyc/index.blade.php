<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-light text-lg text-foreground leading-tight">
                    KYC Management
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-tesla-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-tesla-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Total</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['total'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Pending</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['pending'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Approved</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['approved'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card border border-border p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-2 md:ml-3">
                            <p class="text-xs font-medium text-muted-foreground dark:text-gray-300">Rejected</p>
                            <p class="text-sm md:text-lg font-light text-foreground">{{ $stats['rejected'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-card border border-border rounded-lg mb-6">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <h3 class="text-base font-medium text-foreground">Filter Applications</h3>
                </div>
                <div class="p-4">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.kyc.index') }}" 
                           class="px-3 py-2 text-xs font-medium rounded-lg transition-colors {{ !request('status') ? 'bg-black dark:bg-card text-white dark:text-foreground' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            All ({{ $stats['total'] }})
                        </a>
                        <a href="{{ route('admin.kyc.by-status', 'pending') }}" 
                           class="px-3 py-2 text-xs font-medium rounded-lg transition-colors {{ request('status') === 'pending' ? 'bg-black dark:bg-card text-white dark:text-foreground' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Pending ({{ $stats['pending'] }})
                        </a>
                        <a href="{{ route('admin.kyc.by-status', 'approved') }}" 
                           class="px-3 py-2 text-xs font-medium rounded-lg transition-colors {{ request('status') === 'approved' ? 'bg-black dark:bg-card text-white dark:text-foreground' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Approved ({{ $stats['approved'] }})
                        </a>
                        <a href="{{ route('admin.kyc.by-status', 'rejected') }}" 
                           class="px-3 py-2 text-xs font-medium rounded-lg transition-colors {{ request('status') === 'rejected' ? 'bg-black dark:bg-card text-white dark:text-foreground' : 'bg-muted text-muted-foreground hover:bg-muted' }}">
                            Rejected ({{ $stats['rejected'] }})
                        </a>
                    </div>
                </div>
            </div>

            @if($kycApplications->count() > 0)
            <!-- KYC Applications List -->
            <div class="bg-card border border-border overflow-hidden rounded-lg">
                <div class="px-4 py-3 border-b border-border dark:border-gray-700 bg-muted/40">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-foreground">KYC Applications</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground dark:text-gray-300">{{ $kycApplications->count() }} of {{ $kycApplications->total() }} applications</span>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($kycApplications as $kyc)
                        <div class="bg-muted/40 rounded-lg p-4 border border-border dark:border-gray-700 hover:bg-card hover:shadow-sm transition-all duration-200">
                            <!-- Mobile Layout -->
                            <div class="md:hidden">
                                <div class="flex items-start space-x-3 mb-3">
                                    @if($kyc->user->profile_image)
                                        <img src="{{ asset('storage/' . $kyc->user->profile_image) }}" 
                                             alt="{{ $kyc->user->name }}" 
                                             class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gradient-to-br from-tesla-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">{{ strtoupper(substr($kyc->user->name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-foreground truncate">{{ $kyc->user->name }}</h4>
                                        <p class="text-xs text-muted-foreground truncate">{{ $kyc->user->email }}</p>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $kyc->document_type_label }} • {{ $kyc->full_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $kyc->status_color === 'green' ? 'bg-green-100 text-green-800' : ($kyc->status_color === 'red' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ $kyc->status_label }}
                                        </span>
                                        <p class="text-xs text-muted-foreground mt-1">{{ $kyc->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                
                                <!-- Mobile Actions -->
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.kyc.show', $kyc) }}" 
                                       class="flex-1 px-3 py-2 bg-muted text-muted-foreground text-xs font-medium rounded text-center hover:bg-muted transition-colors">
                                        View
                                    </a>
                                    
                                    @if($kyc->status === 'pending')
                                    <form method="POST" action="{{ route('admin.kyc.approve', $kyc) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full px-3 py-2 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                            Approve
                                        </button>
                                    </form>
                                    
                                    <button onclick="openRejectModal('{{ $kyc->id }}', '{{ $kyc->user->name }}')" 
                                            class="flex-1 px-3 py-2 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                        Reject
                                    </button>
                                    @endif
                                    
                                    <form method="POST" action="{{ route('admin.kyc.destroy', $kyc) }}" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this KYC application?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-full px-3 py-2 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Desktop Layout -->
                            <div class="hidden md:flex items-center justify-between">
                                <div class="flex items-center space-x-4">
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
                                        <p class="text-xs text-muted-foreground mt-1">{{ $kyc->document_type_label }} • {{ $kyc->full_name }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground dark:text-gray-300">{{ $kyc->created_at->format('M d, Y') }}</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $kyc->status_color === 'green' ? 'bg-green-100 text-green-800' : ($kyc->status_color === 'red' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                            {{ $kyc->status_label }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.kyc.show', $kyc) }}" 
                                           class="px-3 py-1.5 bg-muted text-muted-foreground text-xs font-medium rounded hover:bg-muted transition-colors">
                                            View
                                        </a>
                                        
                                        @if($kyc->status === 'pending')
                                        <form method="POST" action="{{ route('admin.kyc.approve', $kyc) }}" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                                Approve
                                            </button>
                                        </form>
                                        
                                        <button onclick="openRejectModal('{{ $kyc->id }}', '{{ $kyc->user->name }}')" 
                                                class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                            Reject
                                        </button>
                                        @endif
                                        
                                        <form method="POST" action="{{ route('admin.kyc.destroy', $kyc) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this KYC application?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="px-3 py-1.5 bg-red-100 text-red-700 text-xs font-medium rounded hover:bg-red-200 transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($kycApplications->hasPages())
                <div class="px-4 py-3 border-t border-border dark:border-gray-700">
                    {{ $kycApplications->links() }}
                </div>
                @endif
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-card border border-border p-8 rounded-lg text-center">
                <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-2">No KYC applications found</h3>
                <p class="text-xs text-muted-foreground dark:text-gray-300">No KYC applications have been submitted yet.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border border-border w-96 max-w-[calc(100vw-2rem)] shadow-lg rounded-xl bg-card text-card-foreground">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-foreground dark:text-white mb-4">Reject KYC Application</h3>
                <p class="text-sm text-muted-foreground mb-4">Are you sure you want to reject the KYC application for <span id="rejectUserName" class="font-medium"></span>?</p>
                
                <form id="rejectForm" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="rejection_reason" class="block text-xs font-medium text-muted-foreground mb-2">Rejection Reason *</label>
                        <textarea id="rejection_reason" 
                                  name="rejection_reason" 
                                  rows="3" 
                                  class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-ring focus:border-black transition-colors duration-200"
                                  placeholder="Please provide a reason for rejection..."
                                  required></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeRejectModal()" 
                                class="px-4 py-2 text-sm font-medium text-muted-foreground bg-muted rounded-lg hover:bg-muted transition-colors">
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
        function openRejectModal(kycId, userName) {
            document.getElementById('rejectUserName').textContent = userName;
            document.getElementById('rejectForm').action = `/admin/kyc/${kycId}/reject`;
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
