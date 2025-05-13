<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Stripe\Stripe;
use Stripe\Refund;
use Illuminate\Support\Facades\Mail;
use App\Mail\RefundProcessedMailable;
use App\Mail\RefundFailedMailable;

class ProcessCancellation implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1) Restock each item
        foreach ($this->order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        // 2) Issue Stripe refund
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            Refund::create([
                'payment_intent' => $this->order->payment->stripe_payment_intent_id,
            ]);

            // 3a) Send “refund processed” email
            Mail::to($this->order->user)
                ->queue(new RefundProcessedMailable($this->order));

        } catch (\Exception $e) {
            // 3b) On failure, send a “refund failed” email
            Mail::to($this->order->user)
                ->queue(new RefundFailedMailable($this->order, $e->getMessage()));
        }
    }
}
