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
use Illuminate\Support\Facades\Http;

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

        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 100,
            'quantity' => 2,
            'total' => 200,
        ]);
        $cart->update(['discount' => 20]);

        // shipping not yet calculated => defaults to 0
        $response = $this->actingAs($user)->get(route('checkout'));

        $expectedTax = round((200 - 20) * 0.1, 2);
        $expectedTotal = round(200 - 20 + $expectedTax + 0, 2);

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
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 50]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 50,
            'quantity' => 2,
            'total' => 100,
        ]);
        $cart->update(['discount' => 10]);
        Config::set('cart.tax_rate', 0);

        // simulate shipping fee in session
        session(['shipping_fee' => 5]);

        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('create')->once()->andReturn((object)[
                'id' => 'pi_123',
                'status' => 'requires_payment_method',
                'client_secret' => 'secret_123',
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
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 30]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 30,
            'quantity' => 1,
            'total' => 30,
        ]);

        $this->actingAs($user)
            ->postJson(route('checkout.applyPromo'), ['code' => 'BAD'])
            ->assertStatus(422)
            ->assertJson(['error' => 'That promo code is invalid.']);
    }

    public function test_apply_promo_success()
    {
        Config::set('cart.tax_rate', 0.2);
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 40]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 40,
            'quantity' => 2,
            'total' => 80,
        ]);
        PromoCode::create([
            'code' => 'SAVE5',
            'type' => 'fixed',
            'discount' => 5,
            'max_uses' => null,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.applyPromo'), ['code' => 'SAVE5'])
            ->assertOk()
            ->assertJsonStructure(['subtotal', 'discount', 'tax', 'total']);

        $data = $response->json();
        $this->assertEquals(80, $data['subtotal']);
        $this->assertEquals(5, $data['discount']);
        $this->assertEquals(round((80 - 5) * 0.2, 2), $data['tax']);
        $this->assertEquals(round(80 - 5 + ((80 - 5) * 0.2), 2), $data['total']);
    }

    public function test_place_order_fails_when_payment_not_succeeded()
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 20]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 20,
            'quantity' => 1,
            'total' => 20,
        ]);

        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('retrieve')->once()->andReturn((object)['status' => 'requires_payment_method']);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.placeOrder'), [
                'shipping_address' => '123 Lane',
                'postal_code' => '99999',
                'country' => 'US',
                'email' => 'test@example.com',
                'payment_intent' => 'pi_fail'
            ]);

        $response
            ->assertStatus(422)
            ->assertJson(['error' => 'Payment not successful']);
    }

    public function test_place_order_successful_flow_records_shipping()
    {
        Config::set('cart.tax_rate', 0);
        Config::set('mail.admin_address', 'admin@example.test');

        // 1) Seed user, cart, item & promo
        $user = User::factory()->create();
        $cart = Cart::create([
            'user_id' => $user->id,
            'promo_code' => 'DISC1',
            'discount' => 1,
        ]);
        $product = Product::factory()->create([
            'price' => 10,
            'weight' => 2,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 10,
            'quantity' => 2,
            'total' => 20,
        ]);
        PromoCode::create([
            'code' => 'DISC1',
            'type' => 'fixed',
            'discount' => 1,
            'expires_at' => now()->addDay(),
            'active' => true,
        ]);

        // 2) Fake all ShipStation HTTP calls
        $base = config('shipping.shipstation.base');
        Http::fake([
            // createorder endpoint
            "{$base}/orders/createorder" => Http::response([
                'orderId' => '1',
            ], 200),

            // createlabel endpoint
            "{$base}/shipments/createlabel" => Http::response([
                'labelId' => 'lbl_123',
                'trackingNumber' => 'TRACK123',
                'shipmentCost' => 5.00,
                'otherCost' => 0.00,
                'labelData' => 'https://example.test/labels/123.pdf',
            ], 200),

            // catch-all so no real HTTP leaks through
            '*' => Http::response([], 200),
        ]);

        // 3) Stub Stripe retrieve to succeed
        Mockery::mock('alias:' . PaymentIntent::class)
            ->shouldReceive('retrieve')
            ->once()
            ->andReturn((object)[
                'status' => 'succeeded',
                'id' => 'pi_success',
            ]);

        // 4) Hit the endpoint
        $this->actingAs($user)
            ->withSession(['shipping_fee' => 0])
            ->postJson(route('checkout.placeOrder'), [
                'shipping_address' => '456 Road',
                'postal_code' => '12345',
                'country' => 'US',
                'email' => 'buyer@example.com',
                'payment_intent' => 'pi_success',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 5) Assertions
        $this->assertDatabaseHas('shipments', [
            'label_id' => 'lbl_123',
            'tracking_number' => 'TRACK123',
        ]);

        Mail::assertQueued(OrderConfirmationMailable::class, fn($m) => $m->hasTo('buyer@example.com')
        );
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
}