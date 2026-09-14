<?php

namespace App\Mail;

use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $emailTemplate;
    public $variables;
    public $processedContent;
    public $processedSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, EmailTemplate $emailTemplate, array $variables = [])
    {
        $this->user = $user;
        $this->emailTemplate = $emailTemplate;
        $this->variables = array_merge([
            'user_name' => $user->name,
            'user_email' => $user->email,
        ], $variables);
        
        $this->processedContent = $emailTemplate->processContent($this->variables);
        $this->processedSubject = $emailTemplate->processSubject($this->variables);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->processedSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-notification',
            with: [
                'user' => $this->user,
                'content' => $this->processedContent,
                'template' => $this->emailTemplate,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
