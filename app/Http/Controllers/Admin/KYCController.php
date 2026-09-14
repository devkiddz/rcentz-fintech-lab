<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\KYCApprovedEmail;
use App\Mail\KYCRejectedEmail;
use App\Models\KYC;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class KYCController extends Controller
{
    /**
     * Display a listing of KYC applications.
     */
    public function index(): View
    {
        $kycApplications = KYC::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => KYC::count(),
            'pending' => KYC::where('status', 'pending')->count(),
            'approved' => KYC::where('status', 'approved')->count(),
            'rejected' => KYC::where('status', 'rejected')->count(),
        ];

        return view('admin.kyc.index', compact('kycApplications', 'stats'));
    }

    /**
     * Display the specified KYC application.
     */
    public function show(KYC $kyc): View
    {
        $kyc->load('user');
        return view('admin.kyc.show', compact('kyc'));
    }

    /**
     * Approve a KYC application.
     */
    public function approve(KYC $kyc): RedirectResponse
    {
        $kyc->update([
            'status' => 'approved',
            'verified_at' => now(),
        ]);

        // Create notification for the user
        NotificationService::createKYCStatusNotification(
            $kyc->user,
            'approved'
        );

        // Send approval email
        Mail::to($kyc->user->email)->send(new KYCApprovedEmail($kyc->user, $kyc));

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC application approved successfully.');
    }

    /**
     * Reject a KYC application.
     */
    public function reject(Request $request, KYC $kyc): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $kyc->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Create notification for the user
        NotificationService::createKYCStatusNotification(
            $kyc->user,
            'rejected',
            $request->rejection_reason
        );

        // Send rejection email
        Mail::to($kyc->user->email)->send(new KYCRejectedEmail($kyc->user, $kyc));

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC application rejected successfully.');
    }

    /**
     * Delete a KYC application.
     */
    public function destroy(KYC $kyc): RedirectResponse
    {
        $kyc->delete();

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC application deleted successfully.');
    }

    /**
     * Show KYC applications by status.
     */
    public function byStatus(string $status): View
    {
        $validStatuses = ['pending', 'approved', 'rejected'];
        
        if (!in_array($status, $validStatuses)) {
            abort(404);
        }

        $kycApplications = KYC::with('user')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => KYC::count(),
            'pending' => KYC::where('status', 'pending')->count(),
            'approved' => KYC::where('status', 'approved')->count(),
            'rejected' => KYC::where('status', 'rejected')->count(),
        ];

        return view('admin.kyc.index', compact('kycApplications', 'stats', 'status'));
    }
}
