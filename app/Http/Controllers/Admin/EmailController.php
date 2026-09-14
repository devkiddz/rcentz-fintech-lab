<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Mail\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Display email templates
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.emails.index', compact('templates'));
    }

    /**
     * Show form to create new email template
     */
    public function createTemplate()
    {
        return view('admin.emails.create-template');
    }

    /**
     * Store new email template
     */
    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:email_templates',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:general,purchase,notification,welcome,reminder',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        EmailTemplate::create([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $request->content,
            'type' => $request->type,
            'variables' => $request->variables ?? [],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.emails.index')
            ->with('success', 'Email template created successfully!');
    }

    /**
     * Show form to edit email template
     */
    public function editTemplate(EmailTemplate $template)
    {
        return view('admin.emails.edit-template', compact('template'));
    }

    /**
     * Update email template
     */
    public function updateTemplate(Request $request, EmailTemplate $template)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:email_templates,name,' . $template->id,
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:general,purchase,notification,welcome,reminder',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $template->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $request->content,
            'type' => $request->type,
            'variables' => $request->variables ?? [],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.emails.index')
            ->with('success', 'Email template updated successfully!');
    }

    /**
     * Delete email template
     */
    public function destroyTemplate(EmailTemplate $template)
    {
        $template->delete();
        
        return redirect()->route('admin.emails.index')
            ->with('success', 'Email template deleted successfully!');
    }

    /**
     * Show form to send email to users
     */
    public function compose()
    {
        $users = User::where('is_admin', false)->orderBy('name')->get();
        $templates = EmailTemplate::active()->orderBy('name')->get();
        
        return view('admin.emails.compose', compact('users', 'templates'));
    }

    /**
     * Send email to selected users
     */
    public function send(Request $request)
    {
        if ($request->template_id === 'custom') {
            // Validate custom template
            $validator = Validator::make($request->all(), [
                'custom_subject' => 'required|string|max:255',
                'custom_content' => 'required|string',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'exists:users,id',
            ]);
        } else {
            // Validate regular template
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|exists:email_templates,id',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'exists:users,id',
                'variables' => 'nullable|array',
            ]);
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $users = User::whereIn('id', $request->recipients)->get();
        $sentCount = 0;
        $failedCount = 0;

        if ($request->template_id === 'custom') {
            // Handle custom template
            foreach ($users as $user) {
                try {
                    // Replace user variables in custom content
                    $subject = str_replace(['{{user_name}}', '{{user_email}}'], [$user->name, $user->email], $request->custom_subject);
                    $content = str_replace(['{{user_name}}', '{{user_email}}'], [$user->name, $user->email], $request->custom_content);
                    
                    Mail::to($user->email)->send(new \App\Mail\CustomEmail($user, $subject, $content));
                    $sentCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    \Log::error('Failed to send custom email to ' . $user->email . ': ' . $e->getMessage());
                }
            }
        } else {
            // Handle regular template
            $template = EmailTemplate::findOrFail($request->template_id);
            $variables = $request->variables ?? [];

            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new UserNotification($user, $template, $variables));
                    $sentCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    \Log::error('Failed to send email to ' . $user->email . ': ' . $e->getMessage());
                }
            }
        }

        $message = "Email sent successfully to {$sentCount} user(s).";
        if ($failedCount > 0) {
            $message .= " {$failedCount} email(s) failed to send.";
        }

        return redirect()->route('admin.emails.compose')
            ->with('success', $message);
    }

    /**
     * Preview email template
     */
    public function preview(Request $request)
    {
        $template = EmailTemplate::findOrFail($request->template_id);
        $variables = $request->variables ?? [];
        
        // Use a sample user for preview
        $sampleUser = new User([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com'
        ]);

        $mail = new UserNotification($sampleUser, $template, $variables);
        
        return response()->json([
            'subject' => $mail->processedSubject,
            'content' => $mail->processedContent,
        ]);
    }
}
