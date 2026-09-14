<?php

namespace App\Http\Controllers;

use App\Mail\KYCSubmittedEmail;
use App\Models\KYC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;

class KYCController extends Controller
{
    /**
     * Show KYC form
     */
    public function index()
    {
        $user = Auth::user();
        $kyc = $user->kyc;
        // Load countries from public/countries.json for select inputs
        $countries = [];
        try {
            $path = public_path('countries.json');
            if (file_exists($path)) {
                $json = file_get_contents($path);
                $arr = json_decode($json, true) ?: [];
                $countries = collect($arr)
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            // If anything fails, fallback to empty list to avoid breaking the page
            $countries = [];
        }

        return view('profile.kyc', compact('kyc', 'countries'));
    }

    /**
     * Submit KYC verification
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user already has KYC
        if ($user->kyc) {
            return back()->withErrors(['error' => 'KYC verification already submitted.']);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'nationality' => 'required|string|max:255',
            'document_type' => 'required|in:passport,national_id,drivers_license',
            'document_number' => 'required|string|max:255',
            'document_expiry_date' => 'required|date|after:today',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state_province' => 'required|string|max:255',
            'postal_code' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'document_front' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'document_back' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'selfie' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            // Upload documents
            $documentFrontPath = $request->file('document_front')->store('kyc/documents', 'public');
            $documentBackPath = $request->file('document_back')->store('kyc/documents', 'public');
            $selfiePath = $request->file('selfie')->store('kyc/selfies', 'public');

            // Create KYC record
            $kyc = $user->kyc()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'date_of_birth' => $request->date_of_birth,
                'nationality' => $request->nationality,
                'document_type' => $request->document_type,
                'document_number' => $request->document_number,
                'document_expiry_date' => $request->document_expiry_date,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'city' => $request->city,
                'state_province' => $request->state_province,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'phone_number' => $request->phone_number,
                'document_front_path' => $documentFrontPath,
                'document_back_path' => $documentBackPath,
                'selfie_path' => $selfiePath,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Create notification
            NotificationService::createKYCStatusNotification($user, 'pending');

            // Send KYC submission email
            Mail::to($user->email)->send(new KYCSubmittedEmail($user, $kyc));

            return redirect()->route('profile.kyc')
                ->with('success', 'KYC verification submitted successfully. We will review your documents within 24-48 hours.');

        } catch (\Exception $e) {
            // Clean up uploaded files if KYC creation fails
            if (isset($documentFrontPath)) Storage::disk('public')->delete($documentFrontPath);
            if (isset($documentBackPath)) Storage::disk('public')->delete($documentBackPath);
            if (isset($selfiePath)) Storage::disk('public')->delete($selfiePath);

            return back()->withErrors(['error' => 'Failed to submit KYC verification. Please try again.']);
        }
    }

    /**
     * Update KYC verification
     */
    public function update(Request $request, KYC $kyc)
    {
        // Ensure user owns this KYC
        if ($kyc->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'nationality' => 'required|string|max:255',
            'document_type' => 'required|in:passport,national_id,drivers_license',
            'document_number' => 'required|string|max:255',
            'document_expiry_date' => 'required|date|after:today',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state_province' => 'required|string|max:255',
            'postal_code' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'document_front' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'document_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'selfie' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $updateData = $request->only([
                'first_name', 'last_name', 'date_of_birth', 'nationality',
                'document_type', 'document_number', 'document_expiry_date',
                'address_line_1', 'address_line_2', 'city', 'state_province',
                'postal_code', 'country', 'phone_number'
            ]);

            // Handle file uploads if provided
            if ($request->hasFile('document_front')) {
                Storage::disk('public')->delete($kyc->document_front_path);
                $updateData['document_front_path'] = $request->file('document_front')->store('kyc/documents', 'public');
            }

            if ($request->hasFile('document_back')) {
                Storage::disk('public')->delete($kyc->document_back_path);
                $updateData['document_back_path'] = $request->file('document_back')->store('kyc/documents', 'public');
            }

            if ($request->hasFile('selfie')) {
                Storage::disk('public')->delete($kyc->selfie_path);
                $updateData['selfie_path'] = $request->file('selfie')->store('kyc/selfies', 'public');
            }

            // Reset status to pending if documents were updated
            if ($request->hasFile('document_front') || $request->hasFile('document_back') || $request->hasFile('selfie')) {
                $updateData['status'] = 'pending';
                $updateData['submitted_at'] = now();
                $updateData['verified_at'] = null;
                $updateData['rejection_reason'] = null;
            }

            $kyc->update($updateData);

            return redirect()->route('profile.kyc')
                ->with('success', 'KYC verification updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update KYC verification. Please try again.']);
        }
    }

    /**
     * Show KYC status
     */
    public function show()
    {
        // KYC status is already represented on the canonical KYC screen.
        // Keeping one page avoids two views drifting out of sync.
        return redirect()->route('profile.kyc');
    }
}
