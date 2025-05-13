<?php

namespace App\Jobs;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReviewRequestMailable;

class ProcessReview implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public Review $review;

    /**
     * Create a new job instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send the review request email (or thank you email)
        Mail::to($this->review->orderItem->order->user->email)
            ->queue(new ReviewRequestMailable($this->review));
    }
}
