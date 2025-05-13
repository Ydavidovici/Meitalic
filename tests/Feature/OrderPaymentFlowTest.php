<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Mockery;
use Stripe\PaymentIntent;
use App\Http\Controllers\PaymentController;

class OrderPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function user_can_place_order_and_payment_is_recorded()
    {

        Config::set('cart.tax_rate', 0);

        // Mock Stripe PaymentIntent creation
        $fakeIntent = (object)['id' => 'pi_test_123'];
        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('create')->once()->andReturn($fakeIntent);

        // Prepare user, cart, items
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([ 'cart_id' => $cart->id, 'product_id' => $product->id, 'name' => $product->name, 'price' => 10, 'quantity' => 2, 'total' => 20 ]);

        $this->actingAs($user)
            ->post(route('checkout.placeOrder'), [
                'shipping_address' => '123 Test Lane',
                'email'            => 'test@example.com',
                'phone'            => '555-0000',
                'payment_intent'   => 'pi_test_123',
            ])
            ->assertOk();

        // Assert order created
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(20, $order->total);
        $this->assertEquals('paid', $order->status);

        // Assert payment recorded
        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertEquals('pi_test_123', $payment->stripe_payment_id);
        $this->assertEquals(20 * 100, $payment->amount);
        $this->assertEquals('pending', $payment->status);
    }

    /** @test */
    public function record_payment_creates_payment_model()
    {
        $order = Order::factory()->create();
        $controller = app(PaymentController::class);
        $controller->recordPayment($order, 'pi_456', 1500);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'stripe_payment_id' => 'pi_456',
            'amount' => 1500,
            'status' => 'pending',
        ]);
    }
}
