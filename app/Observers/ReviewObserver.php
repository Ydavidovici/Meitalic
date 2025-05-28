<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    public function created(Review $review)
    {
        Log::channel('audit')->info('Review created', [
            'model'           => 'Review',
            'action'          => 'created',
            'id'              => $review->id,
            'order_item_id'   => $review->order_item_id,
            'product_id'      => $review->product_id,
            'user_id'         => $review->user_id,
            'rating'          => $review->rating,
            'status'          => $review->status,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    public function updated(Review $review)
    {
        $changes = $review->getChanges();
        Log::channel('audit')->info('Review updated', [
            'model'           => 'Review',
            'action'          => 'updated',
            'id'              => $review->id,
            'changes'         => $changes,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }

    public function deleted(Review $review)
    {
        Log::channel('audit')->info('Review deleted', [
            'model'           => 'Review',
            'action'          => 'deleted',
            'id'              => $review->id,
            'order_item_id'   => $review->order_item_id,
            'product_id'      => $review->product_id,
            'user_id'         => $review->user_id,
            'performed_by'    => auth()->id(),
            'timestamp'       => now()->toIso8601String(),
        ]);
    }
}
