<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PromoCode;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Mockery;
use App\Mail\OrderConfirmationMailable;
use App\Mail\AdminOrderNotificationMail;
use App\Mail\ReviewRequestMailable;
use App\Services\UPSService;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Stripe::setApiKey('sk_test_...');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_checkout_redirects_when_cart_empty()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('checkout'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'Your cart is empty.');
    }

    public function test_checkout_displays_totals_and_shipping_when_cart_has_items()
    {
        Config::set('cart.tax_rate', 0.1);

        $user    = User::factory()->create();
        $cart    = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 100,
            'quantity'   => 2,
            'total'      => 200,
        ]);
        $cart->update(['discount' => 20]);

        // shipping not yet calculated => defaults to 0
        $response = $this->actingAs($user)->get(route('checkout'));

        $expectedTax     = round((200 - 20) * 0.1, 2);
        $expectedTotal   = round(200 - 20 + $expectedTax + 0, 2);

        $response
            ->assertOk()
            ->assertViewIs('pages.checkout.index')
            ->assertViewHas('subtotal', 200)
            ->assertViewHas('discount', 20)
            ->assertViewHas('tax', $expectedTax)
            ->assertViewHas('shipping', 0)
            ->assertViewHas('total', $expectedTotal);
    }

    public function test_payment_intent_errors_when_cart_empty()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('checkout.paymentIntent'))
            ->assertStatus(422)
            ->assertJson(['error' => 'Your cart is empty']);
    }

    public function test_payment_intent_returns_client_secret_and_amount_including_shipping()
    {
        $user    = User::factory()->create();
        $cart    = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 50]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 50,
            'quantity'   => 2,
            'total'      => 100,
        ]);
        $cart->update(['discount' => 10]);
        Config::set('cart.tax_rate', 0);

        // simulate shipping fee in session
        session(['shipping_fee' => 5]);

        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('create')->once()->andReturn((object)[
                'client_secret' => 'secret_123'
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.paymentIntent'))
            ->assertOk()
            ->assertJsonStructure(['clientSecret', 'amount']);

        // (100 - 10 + shipping 5) / 100 = 0.95
        $this->assertEquals('secret_123', $response->json('clientSecret'));
        $this->assertEquals((100 - 10 + 5) / 100, $response->json('amount'));
    }

    public function test_apply_promo_errors_when_cart_empty()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('checkout.applyPromo'), ['code' => 'ANY'])
            ->assertStatus(422)
            ->assertJson(['error' => 'Your cart is empty.']);
    }

    public function test_apply_promo_errors_for_invalid_code()
    {
        $user    = User::factory()->create();
        $cart    = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 30]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 30,
            'quantity'   => 1,
            'total'      => 30,
        ]);

        $this->actingAs($user)
            ->postJson(route('checkout.applyPromo'), ['code' => 'BAD'])
            ->assertStatus(422)
            ->assertJson(['error' => 'That promo code is invalid.']);
    }

    public function test_apply_promo_success()
    {
        Config::set('cart.tax_rate', 0.2);
        $user    = User::factory()->create();
        $cart    = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 40]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 40,
            'quantity'   => 2,
            'total'      => 80,
        ]);
        PromoCode::create([
            'code'       => 'SAVE5',
            'type'       => 'fixed',
            'discount'   => 5,
            'max_uses'   => null,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'active'     => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.applyPromo'), ['code' => 'SAVE5'])
            ->assertOk()
            ->assertJsonStructure(['subtotal','discount','tax','total']);

        $data = $response->json();
        $this->assertEquals(80, $data['subtotal']);
        $this->assertEquals(5, $data['discount']);
        $this->assertEquals(round((80 - 5) * 0.2, 2), $data['tax']);
        $this->assertEquals(round(80 - 5 + ((80 - 5) * 0.2), 2), $data['total']);
    }

    public function test_place_order_fails_when_payment_not_succeeded()
    {
        $user    = User::factory()->create();
        $cart    = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 20]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 20,
            'quantity'   => 1,
            'total'      => 20,
        ]);

        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('retrieve')->once()->andReturn((object)['status' => 'requires_payment_method']);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.placeOrder'), [
                'shipping_address' => '123 Lane',
                'email'            => 'test@example.com',
                'payment_intent'   => 'pi_fail'
            ]);

        $response
            ->assertStatus(422)
            ->assertJson(['error' => 'Payment not successful']);
    }

    public function test_place_order_successful_flow_records_shipping()
    {
        Config::set('cart.tax_rate', 0);
        $user    = User::factory()->create();
        $cart    = Cart::create([
            'user_id'    => $user->id,
            'promo_code' => 'DISC1',
            'discount'   => 1
        ]);
        $product = Product::factory()->create(['price' => 10]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 10,
            'quantity'   => 2,
            'total'      => 20,
        ]);
        PromoCode::create([
            'code'       => 'DISC1',
            'type'       => 'fixed',
            'discount'   => 1,
            'max_uses'   => null,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'active'     => true,
        ]);

        // simulate free shipping threshold not met => shipping_fee = 0
        session(['shipping_fee' => 0]);

        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('retrieve')->once()->andReturn((object)['status' => 'succeeded']);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.placeOrder'), [
                'shipping_address' => '456 Road',
                'email'            => 'buyer@example.com',
                'payment_intent'   => 'pi_success'
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(0, $order->shipping_fee);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('promo_codes', ['code' => 'DISC1', 'used_count' => 1]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('carts', [
            'id'         => $cart->id,
            'discount'   => 0,
            'promo_code' => null
        ]);

        Mail::assertQueued(OrderConfirmationMailable::class, fn($m) =>
        $m->hasTo('buyer@example.com'));
        Mail::assertQueued(AdminOrderNotificationMail::class);
        Mail::assertQueued(ReviewRequestMailable::class);
    }

    public function test_success_page_shows_thank_you()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('checkout.success'))
            ->assertOk()
            ->assertViewIs('pages.checkout.success');
    }

    /** @test */
    public function calculate_shipping_free_above_threshold()
    {
        // Given a free‐shipping threshold of $50
        Config::set('shipping.free_threshold', 50);

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);

        // Add a single item with price 60 (subtotal = 60)
        $product = Product::factory()->create([
            'price'    => 60,
            'weight'   => 1,
            'length'   => 5,
            'width'    => 5,
            'height'   => 5,
        ]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 60,
            'quantity'   => 1,
            'total'      => 60,
        ]);

        // Swap in a UPSService mock that must NOT be called
        $ups = Mockery::mock(UPSService::class);
        $ups->shouldNotReceive('getRate');
        $this->app->instance(UPSService::class, $ups);

        // When we calculate shipping
        $response = $this->actingAs($user)
            ->postJson(route('checkout.shipping'), [
                'shipping_address' => '123 Lane'
            ]);

        // Then we get free shipping (0) and it's saved to session
        $response
            ->assertOk()
            ->assertJson(['shipping' => 0]);

        $this->assertEquals(0, session('shipping_fee'));
    }

    /** @test */
    public function calculate_shipping_uses_ups_service_when_below_threshold()
    {
        Config::set('shipping.free_threshold', 100);

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'price'  => 20,
            'weight' => 2.5,
            'length' => 10,
            'width'  => 8,
            'height' => 4,
        ]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 20,
            'quantity'   => 1,
            'total'      => 20,
        ]);

        // figure out which box PackagingService will pick
        $box         = config('shipping.boxes')[1];
        $expectedDims = [
            'length' => $box['length'],
            'width'  => $box['width'],
            'height' => $box['height'],
        ];

        $ups = Mockery::mock(UPSService::class);
        $ups->shouldReceive('getRate')
            ->once()
            ->withArgs(function ($shipTo, $weight, $dims) use ($expectedDims) {
                return $shipTo === ['AddressLine' => '123 Lane']
                    && $weight  === 2.5
                    && $dims    === $expectedDims;
            })
            ->andReturn(12.34);

        $this->app->instance(UPSService::class, $ups);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.shipping'), ['shipping_address' => '123 Lane']);

        $response
            ->assertOk()
            ->assertJson(['shipping' => 12.34]);

        $this->assertEquals(12.34, session('shipping_fee'));
    }



    /** @test */
    public function calculate_shipping_fits_envelope_and_calls_ups()
    {
        Config::set('shipping.free_threshold', 100);

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'price'  => 5,
            'weight' => 0.2,
            'length' => 8,
            'width'  => 5,
            'height' => 0.4,
        ]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 5,
            'quantity'   => 1,
            'total'      => 5,
        ]);

        $envelope = config('shipping.envelope');

        $ups = Mockery::mock(UPSService::class);
        $ups->shouldReceive('getRate')
            ->once()
            ->withArgs(function($shipTo, $weight, $dims) use ($envelope) {
                return $shipTo['AddressLine'] === '123 Lane'
                    && $weight === 0.2
                    && $dims === [
                        'length' => $envelope['length'],
                        'width'  => $envelope['width'],
                        'height' => $envelope['height'],
                    ];
            })
            ->andReturn(2.50);

        $this->app->instance(UPSService::class, $ups);

        $this->actingAs($user)
            ->postJson(route('checkout.shipping'), ['shipping_address' => '123 Lane'])
            ->assertOk()
            ->assertJson(['shipping' => 2.50]);

        $this->assertEquals(2.50, session('shipping_fee'));
    }

    /** @test */
    public function calculate_shipping_requires_box_and_calls_ups()
    {
        Config::set('shipping.free_threshold', 100);

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'price'  => 20,
            'weight' => 2.5,
            'length' => 12,
            'width'  => 9,
            'height' => 6,
        ]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 20,
            'quantity'   => 1,
            'total'      => 20,
        ]);

        $boxes = config('shipping.boxes');
        // pick the second box (12×9×6)
        $box = $boxes[1];

        $ups = Mockery::mock(UPSService::class);
        // we only send length/width/height to UPS
        $expectedDims = [
            'length' => $box['length'],
            'width'  => $box['width'],
            'height' => $box['height'],
        ];
        $ups->shouldReceive('getRate')
            ->once()
            ->withArgs(function($shipTo, $weight, $dims) use ($expectedDims) {
                return $shipTo['AddressLine'] === '123 Lane'
                    && $weight === 2.5
                    && $dims === $expectedDims;
            })
            ->andReturn(10.00);

        $this->app->instance(UPSService::class, $ups);

        $this->actingAs($user)
            ->postJson(route('checkout.shipping'), ['shipping_address' => '123 Lane'])
            ->assertOk()
            ->assertJson(['shipping' => 10.00]);

        $this->assertEquals(10.00, session('shipping_fee'));
    }
}
