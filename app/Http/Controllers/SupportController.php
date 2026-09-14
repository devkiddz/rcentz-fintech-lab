<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function index()
    {
        $categories = [
            'General Inquiry', 'Account', 'KYC', 'Wallet', 'Deposits', 'Withdrawals', 'Investments', 'Stocks', 'Technical Issue'
        ];

        return view('support.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|min:10',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        $user = $request->user();

        // Attempt to send an email to support. We intentionally avoid non-error logging.
        try {
            $to = config('support.email', config('mail.from.address'));

            // Build a simple HTML body
            $html = view('emails.support_request', [
                'user' => $user,
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'bodyMessage' => $validated['message'],
            ])->render();

            Mail::send([], [], function ($message) use ($to, $user, $validated, $html, $request) {
                $message->to($to)
                    ->subject('[Support] ' . $validated['subject'])
                    ->setBody($html, 'text/html');

                if ($request->hasFile('attachment')) {
                    $uploaded = $request->file('attachment');
                    $message->attach($uploaded->getRealPath(), [
                        'as' => $uploaded->getClientOriginalName(),
                        'mime' => $uploaded->getMimeType(),
                    ]);
                }
            });

            return back()->with('success', 'Your message has been sent. Our team will get back to you shortly.');
        } catch (\Throwable $e) {
            Log::error('Support form email failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'We could not send your message right now. Please try again later.');
        }
    }
}


