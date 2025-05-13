<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Jobs\NotifyReturnReceived;
use App\Jobs\ProcessReturn;
use App\Jobs\ProcessCancellation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\Refund;
use App\Mail\ReturnProcessedMailable;
use App\Mail\OrderCancelledMailable;

class ReturnAndCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_requesting_return_updates_status_and_dispatches_notify_job()
    {
        Bus::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status'  => 'delivered',
        ]);

        $this->actingAs($user)
            ->post(route('order.return', $order))
            ->assertRedirect()
            ->assertSessionHas('success', 'Your return request has been submitted.');

        $this->assertEquals('pending_return', $order->fresh()->status);

        Bus::assertDispatched(NotifyReturnReceived::class, fn($job) =>
            $job->order->id === $order->id
        );
    }

    public function test_admin_approving_return_updates_status_and_dispatches_process_return_job()
    {
        Bus::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->create(['status' => 'pending_return']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.return.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('success', 'Return approved and refund is being processed.');

        $this->assertEquals('returned', $order->fresh()->status);

        Bus::assertDispatched(ProcessReturn::class, fn($job) =>
            $job->order->id === $order->id
        );
    }

    public function test_admin_rejecting_return_updates_status_and_does_not_dispatch_refund_job()
    {
        Bus::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->create(['status' => 'pending_return']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.return.reject', $order))
            ->assertRedirect()
            ->assertSessionHas('success', 'Return rejected.');

        $this->assertEquals('return_rejected', $order->fresh()->status);

        Bus::assertNotDispatched(ProcessReturn::class);
    }

    public function test_process_return_job_restores_stock_and_queues_email()
    {
        Mail::fake();

        // Stub Stripe refund so we don't hit Stripe internals
        Mockery::mock('alias:' . Refund::class)
            ->shouldReceive('create')
            ->once()
            ->with(['payment_intent' => 'pi_test_123'])
            ->andReturn((object)[]);

        $product = Product::factory()->create(['inventory' => 5]);
        $order   = Order::factory()->create(['total' => 100]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);
        Payment::factory()->create([
            'order_id'          => $order->id,
            'stripe_payment_id' => 'pi_test_123',
        ]);

        $job = new ProcessReturn($order);
        $job->handle();

        $this->assertDatabaseHas('products', [
            'id'        => $product->id,
            'inventory' => 8,
        ]);

        Mail::assertQueued(ReturnProcessedMailable::class, fn($mail) =>
            $mail->order->id === $order->id
        );
    }

    public function test_customer_can_cancel_order_and_triggers_cancellation_flow()
    {
        Bus::fake();
        Mail::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        $this->actingAs($user)
            ->patch(route('order.cancel', $order))
            ->assertRedirect()
            ->assertSessionHas('success', 'Your order has been canceled; refund is processing.');

        $this->assertEquals('canceled', $order->fresh()->status);

        Mail::assertQueued(OrderCancelledMailable::class, fn($mail) =>
            $mail->order->id === $order->id
        );

        Bus::assertDispatched(ProcessCancellation::class, fn($job) =>
            $job->order->id === $order->id
        );
    }

    public function test_cannot_cancel_order_after_shipping()
    {
        Bus::fake();
        Mail::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status'  => 'shipped',
        ]);

        $this->actingAs($user)
            ->patch(route('order.cancel', $order))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertEquals('shipped', $order->fresh()->status);

        Mail::assertNothingQueued();
        Bus::assertNotDispatched(ProcessCancellation::class);
    }
}
