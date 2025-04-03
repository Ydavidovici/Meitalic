<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceOrdertest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_order_and_payment_is_recorded()
    {
        // Create a user and a product
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 10.00, // Set a fixed price for predictable totals
        ]);

        // Prepare a dummy cart: 2 units of the product, so expected total is $20.00
        $cart = [
            [
                'product_id' => $product->id,
                'quantity'   => 2,
            ],
        ];

        // Authenticate as the user
        $this->actingAs($user);

        // Place the order via POST to /order/place (adjust route if necessary)
        $response = $this->post('/order/place', [
            'shipping_address' => '123 Test Lane',
            'cart'             => $cart,
        ]);

        // Assert that the response is a redirect (order placement success)
        $response->assertRedirect();

        // Verify that an order was created with the correct total ($20.00)
        $order = Order::first();
        $this->assertNotNull($order, 'Order was not created');
        $this->assertEquals(20.00, $order->total, 'Order total does not match expected amount');

        // Verify that a Payment record was created with status 'pending' and amount $20.00
        $payment = Payment::first();
        $this->assertNotNull($payment, 'Payment record was not created');
        $this->assertEquals('pending', $payment->status, 'Payment status is not pending');
        $this->assertEquals(20.00, $payment->amount, 'Payment amount does not match order total');
    }
}
