<x-user-layout>
    <x-slot name="header">
        Notifications
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-light text-foreground mb-1">Notifications</h1>
                    <p class="text-sm text-muted-foreground">{{ $unreadCount }} unread notifications</p>
                </div>
                @if($unreadCount > 0)
                    <button onclick="markAllAsRead()" 
                            class="px-4 py-2 bg-foreground text-background text-sm font-medium rounded-lg hover:opacity-90 transition-colors duration-200 flex items-center">
                        <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                        Mark All as Read
                    </button>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        <div class="space-y-4">
            @forelse($notifications as $notification)
                <div class="bg-card rounded-xl p-6 shadow-sm border border-gray-100 {{ !$notification->is_read ? 'border-l-4 border-l-tesla-500' : '' }}">
                    <div class="flex items-start space-x-4">
                        <!-- Icon -->
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $notification->color === 'blue' ? 'bg-tesla-100' : '' }} {{ $notification->color === 'green' ? 'bg-green-100' : '' }} {{ $notification->color === 'purple' ? 'bg-purple-100' : '' }} {{ $notification->color === 'yellow' ? 'bg-yellow-100' : '' }} {{ $notification->color === 'indigo' ? 'bg-indigo-100' : '' }} {{ $notification->color === 'orange' ? 'bg-orange-100' : '' }} {{ $notification->color === 'gray' ? 'bg-muted' : '' }}">
                            <i data-lucide="{{ $notification->icon }}" 
                               class="w-5 h-5 {{ $notification->color === 'blue' ? 'text-tesla-600' : '' }} {{ $notification->color === 'green' ? 'text-green-600' : '' }} {{ $notification->color === 'purple' ? 'text-purple-600' : '' }} {{ $notification->color === 'yellow' ? 'text-yellow-600' : '' }} {{ $notification->color === 'indigo' ? 'text-indigo-600' : '' }} {{ $notification->color === 'orange' ? 'text-orange-600' : '' }} {{ $notification->color === 'gray' ? 'text-muted-foreground' : '' }}">
                            </i>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-foreground mb-1">
                                        {{ $notification->title }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground mb-2">
                                        {{ $notification->message }}
                                    </p>
                                    <div class="flex items-center space-x-4 text-xs text-muted-foreground dark:text-gray-300">
                                        <span>{{ $notification->formatted_time }}</span>
                                        @if(!$notification->is_read)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-tesla-100 text-tesla-800">
                                                New
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2 ml-4">
                                    @if(!$notification->is_read)
                                        <button onclick="markAsRead({{ $notification->id }})" 
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-muted-foreground hover:bg-muted rounded-lg transition-colors duration-200"
                                                title="Mark as read">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                    <button onclick="deleteNotification({{ $notification->id }})" 
                                            class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                            title="Delete notification">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-card rounded-xl p-8 shadow-sm border border-gray-100 text-center">
                    <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bell" class="w-8 h-8 text-gray-400 dark:text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Notifications</h3>
                    <p class="text-muted-foreground">You're all caught up! No notifications to show.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

    <script>
        function markAsRead(notificationId) {
            fetch(`/notifications/${notificationId}/read`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to update the UI
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }

        function markAllAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to update the UI
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        }

        function deleteNotification(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
                fetch(`/notifications/${notificationId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to update the UI
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error deleting notification:', error);
                });
            }
        }
    </script>
</x-user-layout>
