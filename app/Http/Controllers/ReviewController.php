<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Jobs\ProcessReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Show the “leave a review” form for a specific line‑item.
     */
    public function create(Order $order, OrderItem $item)
    {
        // ensure this order belongs to the user
        if ($order->user_id !== auth()->id() || $item->order_id !== $order->id) {
            abort(403);
        }

        // if they already left one, preload it
        $review = $item->review;

        return view('reviews.create', compact('order', 'item', 'review'));
    }

    /**
     * Persist a new review and dispatch the promo‐code job.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'product_id'    => 'required|exists:products,id',
            'rating'        => 'required|integer|min:1|max:5',
            'body'          => 'nullable|string',
        ]);

        $item = OrderItem::findOrFail($data['order_item_id']);

        // guard: only owner may review
        if ($item->order->user_id !== auth()->id()) {
            abort(403);
        }

        $data['user_id'] = auth()->id();

        $review = Review::create($data);

        // generate promo and send "thanks for reviewing" email
        ProcessReview::dispatch($review);

        return redirect()
            ->route('dashboard.orders')
            ->with('success', 'Thanks for your review!');
    }

    /**
     * Show the “edit your review” form.
     */
    public function edit(Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            abort(403);
        }

        // We need the order & item to reconstruct breadcrumbs
        $item  = $review->orderItem;
        $order = $item->order;

        return view('reviews.create', compact('order', 'item', 'review'));
    }

    /**
     * Update an existing review.
     */
    public function update(Request $request, Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body'   => 'nullable|string',
        ]);

        $review->update($data);

        return redirect()
            ->route('dashboard.orders')
            ->with('success', 'Your review has been updated.');
    }
}
