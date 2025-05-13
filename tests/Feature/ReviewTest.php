<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use App\Jobs\ProcessReview;
use App\Mail\ReviewRequestMailable;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_persists_review_and_dispatches_job()
    {
        Bus::fake();

        $user    = User::factory()->create();
        $product = Product::factory()->create();
        $order   = Order::factory()->create(['user_id' => $user->id]);
        $item    = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.reviews.store'), [
                'order_item_id' => $item->id,
                'product_id'    => $product->id,
                'rating'        => 4,
                'body'          => 'Great product!',
            ])
            ->assertRedirect(route('dashboard.orders'))
            ->assertSessionHas('success', 'Thanks for your review!');

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $item->id,
            'rating'        => 4,
        ]);

        Bus::assertDispatched(ProcessReview::class, fn($job) =>
            $job->review->order_item_id === $item->id
        );
    }

    public function test_update_changes_review_and_redirects()
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $item  = OrderItem::factory()->create(['order_id' => $order->id]);
        $review = Review::factory()->create([
            'user_id'       => $user->id,
            'order_item_id' => $item->id,
        ]);

        $this->actingAs($user)
            ->patch(route('dashboard.reviews.update', $review), [
                'rating' => 5,
                'body'   => 'Updated!',
            ])
            ->assertRedirect(route('dashboard.orders'))
            ->assertSessionHas('success', 'Your review has been updated.');

        $this->assertDatabaseHas('reviews', [
            'id'     => $review->id,
            'rating' => 5,
        ]);
    }
}
