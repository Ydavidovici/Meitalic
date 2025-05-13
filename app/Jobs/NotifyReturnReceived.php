<?php

// app/Jobs/NotifyReturnReceived.php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReturnRequestMailable;

class NotifyReturnReceived implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        Mail::to($this->order->user->email)
            ->queue(new ReturnRequestMailable($this->order));
    }
}
