<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\SignalDelivery;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    /**
     * Get user notifications.
     */
    public function index()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $notifications->setCollection(
            $this->decorateActionUrls($notifications->getCollection(), $user)
        );

        $unreadCount = $user->notifications()->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get notifications for dropdown (API endpoint).
     */
    public function getNotifications()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $notifications = $this->decorateActionUrls($notifications, $user);
        $unreadCount = $user->notifications()->unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        $user->notifications()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount()
    {
        $count = Auth::user()->notifications()->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Delete notification.
     */
    public function destroy(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Add safe action URLs at read time so existing Signal notifications created
     * before the customer Signal workspace existed become immediately clickable.
     */
    private function decorateActionUrls(Collection $notifications, User $user): Collection
    {
        $signalIds = $notifications
            ->where('type', 'signal')
            ->map(fn (Notification $notification) => (int) data_get($notification->data, 'signal_id', 0))
            ->filter()
            ->unique()
            ->values();

        $deliveredSignalIds = $signalIds->isEmpty()
            ? collect()
            : SignalDelivery::query()
                ->where('user_id', $user->id)
                ->whereIn('signal_id', $signalIds)
                ->pluck('signal_id')
                ->map(fn ($id) => (int) $id)
                ->flip();

        return $notifications->map(function (Notification $notification) use ($user, $deliveredSignalIds) {
            $data = (array) ($notification->data ?? []);
            $signalId = (int) ($data['signal_id'] ?? 0);

            if (
                $notification->type === 'signal' &&
                $signalId > 0 &&
                $deliveredSignalIds->has($signalId) &&
                Route::has('signals.show')
            ) {
                $data['action_url'] = route('signals.show', $signalId, false);
                $notification->setAttribute('data', $data);
            }

            if (
                $notification->type === 'signal_admin' &&
                $user->isAdmin() &&
                $signalId > 0 &&
                Route::has('admin.signals.show')
            ) {
                $data['action_url'] = route('admin.signals.show', $signalId, false);
                $notification->setAttribute('data', $data);
            }

            return $notification;
        });
    }
}
