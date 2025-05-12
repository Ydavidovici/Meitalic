<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Cart;
use App\Models\CartItem;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent real Stripe & mail calls
        Stripe::setApiKey('sk_test_...');
        Mail::fake();

        // Mock PaymentIntent::create & retrieve
        Mockery::mock('overload:' . PaymentIntent::class)
            ->shouldReceive('create')
            ->andReturn((object)[
                'id'            => 'pi_test_123',
                'client_secret'=> 'secret_456',
                'status'        => 'requires_payment_method',
            ])
            ->shouldReceive('retrieve')
            ->andReturn((object)[
                'id'     => 'pi_test_123',
                'status' => 'succeeded',
            ]);
    }

    /** @test */
    public function full_checkout_flow_dumps_every_step()
    {
        // 1) Create user & log in
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2) Seed a product
        $product = Product::factory()->create([
            'name'  => 'Test Prod',
            'price' => 25.00,
        ]);

        // 3) Seed cart + cart item in database
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => $product->price,
            'quantity'   => 2,
            'total'      => $product->price * 2,
        ]);


        // 4) Seed a promo code
        PromoCode::create([
            'code'       => 'DISC5',
            'type'       => 'fixed',
            'discount'   => 5.00,
            'max_uses'   => null,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'active'     => true,
        ]);

        // 5) Apply promo
        $apply = $this->postJson(route('checkout.applyPromo'), [
            'code' => 'DISC5',
        ]);
        $apply->dump();      // see subtotal, discount, tax, total
        $apply->assertOk();

        // 6) Create PaymentIntent
        $intent = $this->postJson(route('checkout.paymentIntent'));
        $intent->dump();     // see clientSecret & amount
        $intent->assertOk();

        // 7) Place the order
        $place = $this->postJson(route('checkout.placeOrder'), [
            'shipping_address' => '123 Main St',
            'email'            => 'you@example.com',
            'phone'            => '555-1212',
            'payment_intent'   => 'pi_test_123',
        ]);
        $place->dump();      // see success & order_id
        $place->assertOk();
    }
}
