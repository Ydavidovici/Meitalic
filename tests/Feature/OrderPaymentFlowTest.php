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
use App\Services\ShipStationService;

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

        $this->mock(ShipStationService::class, function ($mock) {
            // 1) fake rates so your controller will pick ups_ground
            $mock->shouldReceive('getRates')
                ->andReturn([ ['serviceCode' => 'ups_ground', 'cost' => 0] ]);

            // 2) fake the initial shipment‐creation call
            $mock->shouldReceive('createShipment')
                ->andReturn([ 'orderId' => 1 ]);

            // 3) fake the subsequent label‐creation call
            $mock->shouldReceive('createLabel')
                ->andReturn([
                    'labelId'        => 'fake_lbl',
                    'trackingNumber' => 'TRACK123',
                    'shipmentCost'   => 0,
                    'otherCost'      => 0,
                    'labelData'      => 'data',
                ]);
        }); 


        // 1) Mock Stripe::PaymentIntent::retrieve() to return a succeeded intent
        $fakeIntent = (object)[
            'id'     => 'pi_test_123',
            'status' => 'succeeded',
        ];
        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('retrieve')
            ->once()
            ->with('pi_test_123')
            ->andReturn($fakeIntent);

        // 2) Prepare user, cart and items
        $user    = User::factory()->create();
        $product = Product::factory()->create(['price' => 10]);
        $cart    = Cart::create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 10,
            'quantity'   => 2,
            'total'      => 20,
        ]);

        // 3) Hit the placeOrder endpoint as JSON
            $this->actingAs($user)
                ->postJson(route('checkout.placeOrder'), [
                    'shipping_address' => '123 Test Lane',
                    'postal_code'      => '12345',
                    'country'          => 'US',
                    'email'            => 'test@example.com',
                    'phone'            => '555-0000',
                    'payment_intent'   => 'pi_test_123',
                ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 4) Order should be created and marked paid
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(20, $order->total);
        $this->assertEquals('paid', $order->status);

        // 5) Payment record should exist with pending status
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
