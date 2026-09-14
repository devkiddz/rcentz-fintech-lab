<?php

namespace App\Mail;

use App\Models\InvestmentTransaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvestmentSellEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $transaction;
    public $totalPortfolioValue;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, InvestmentTransaction $transaction, $totalPortfolioValue = 0)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        $this->totalPortfolioValue = $totalPortfolioValue;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Investment Sale Confirmed - ' . site_name(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.investment-sell',
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
