<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build()
    {
        return $this
            ->subject("Your Order #{$this->order->id} Confirmation")
            ->view('emails.orders.placed')
            ->with(['order'=>$this->order]);
    }
}
