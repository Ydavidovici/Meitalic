<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Stripe\PaymentIntent;

class OrderPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Close Mockery to avoid memory leaks
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_can_place_order_and_payment_is_recorded()
    {
        // 1. Mock the Stripe PaymentIntent::create() call
        $fakeIntent = (object)['id' => 'pi_test_123'];
        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($fakeIntent);

        // 2. Create a user and a product
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 10.00, // fixed price
        ]);

        // 3. Prepare a dummy cart: 2 units => total $20.00
        $cart = [
            ['product_id' => $product->id, 'quantity' => 2],
        ];

        // 4. Authenticate as the user
        $this->actingAs($user);

        // 5. Place the order via the named route
        $response = $this->post(route('order.place'), [
            'shipping_address' => '123 Test Lane',
            'cart'             => $cart,
        ]);

        // 6. Assert redirect
        $response->assertRedirect();

        // 7. Assert Order record
        $order = Order::first();
        $this->assertNotNull($order, 'Order was not created');
        $this->assertEquals(20.00, $order->total, 'Order total mismatch');

        // 8. Assert Payment record (with the fake stripe_payment_id)
        $payment = Payment::first();
        $this->assertNotNull($payment, 'Payment record was not created');
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(20.00, $payment->amount);
        $this->assertEquals('pi_test_123', $payment->stripe_payment_id);
    }
}
