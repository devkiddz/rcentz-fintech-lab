<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Car;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['user', 'car', 'paymentMethod'])
            ->orderBy('purchased_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('purchased_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchased_at', '<=', $request->date_to);
        }

        // Search by car title or user name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('car', function ($carQuery) use ($search) {
                    $carQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        $purchases = $query->paginate(20);

        // Calculate statistics
        $totalRevenue = Purchase::where('status', 'completed')->sum('amount');
        $totalPurchases = Purchase::count();
        $pendingPurchases = Purchase::where('status', 'pending')->count();
        $completedPurchases = Purchase::where('status', 'completed')->count();

        // Get users for filter dropdown
        $users = User::orderBy('name')->get();

        return view('admin.purchases.index', compact(
            'purchases',
            'totalRevenue',
            'totalPurchases',
            'pendingPurchases',
            'completedPurchases',
            'users'
        ));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['user', 'car', 'paymentMethod']);
        
        return view('admin.purchases.show', compact('purchase'));
    }

    public function updateStatus(Request $request, Purchase $purchase)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled,refunded'
        ]);

        $oldStatus = $purchase->status;
        $newStatus = $request->status;

        // Update purchase status
        $purchase->update([
            'status' => $newStatus
        ]);

        // Handle car availability based on status change
        $car = $purchase->car;
        
        // If status changed to cancelled or refunded, make car available again
        if (in_array($newStatus, ['cancelled', 'refunded']) && !in_array($oldStatus, ['cancelled', 'refunded'])) {
            $car->update(['is_available' => true]);
            $message = 'Purchase status updated successfully. Car is now available for sale again.';
        }
        // If status changed from cancelled/refunded to active status, make car unavailable
        elseif (in_array($oldStatus, ['cancelled', 'refunded']) && !in_array($newStatus, ['cancelled', 'refunded'])) {
            $car->update(['is_available' => false]);
            $message = 'Purchase status updated successfully. Car is no longer available for sale.';
        }
        else {
            $message = 'Purchase status updated successfully.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Purchase $purchase)
    {
        $car = $purchase->car;
        $customerName = $purchase->user->name;
        $carTitle = $car->title;
        
        // If the purchase was not cancelled or refunded, make the car available again
        if (!in_array($purchase->status, ['cancelled', 'refunded'])) {
            $car->update(['is_available' => true]);
        }
        
        // Delete the purchase record
        $purchase->delete();
        
        return redirect()->route('admin.purchases.index')
            ->with('success', "Purchase record for {$customerName} ({$carTitle}) has been deleted successfully. Car availability updated accordingly.");
    }
} 