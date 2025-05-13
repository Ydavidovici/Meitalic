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
use App\Mail\ReturnProcessedMailable;
use App\Mail\RefundFailedMailable;

class ProcessReturn implements ShouldQueue
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
            $item->product->increment('inventory', $item->quantity);
        }

        // 2) Issue Stripe refund
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            Refund::create([
                'payment_intent' => $this->order->payment->stripe_payment_id,
            ]);

            // Send the "return processed" email
            Mail::to($this->order->user->email)
                ->queue(new ReturnProcessedMailable($this->order));

        } catch (\Exception $e) {
            // On failure, send the "refund failed" email
            Mail::to($this->order->user->email)
                ->queue(new RefundFailedMailable($this->order));
        }
    }
}
