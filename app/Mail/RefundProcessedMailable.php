<?php
// app/Mail/RefundProcessedMailable.php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RefundProcessedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Refund Processed — #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.refund_processed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
