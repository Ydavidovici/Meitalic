<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent real Stripe API calls
        Stripe::setApiKey('sk_test_...');
        // Mock StripeSession::create to return a dummy object
        \Mockery::mock('overload:' . StripeSession::class)
            ->shouldReceive('create')
            ->andReturn((object)[
                'id'  => 'cs_test_123',
                'url' => 'https://checkout.stripe.test/session/123',
            ]);
    }

    /** @test */
    public function apply_promo_rejects_invalid_code()
    {
        // put one item into session cart
        $this->withSession([
            'cart' => [
                ['product_id'=>1,'name'=>'Foo','price'=>10.00,'quantity'=>2],
            ]
        ])->postJson(route('checkout.applyPromo'), ['code'=>'NOPE'])
            ->assertStatus(422)
            ->assertJson(['error'=>'That promo code is invalid.']);
    }

    /** @test */
    public function apply_promo_returns_correct_discount_for_fixed_code()
    {
        PromoCode::create([
            'code'       => 'SAVE5',
            'type'       => 'fixed',
            'discount'   => 5.00,
            'max_uses'   => null,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'active'     => true,
        ]);

        $this->withSession([
            'cart' => [
                ['product_id'=>1,'name'=>'Foo','price'=>20.00,'quantity'=>1],
                ['product_id'=>2,'name'=>'Bar','price'=>10.00,'quantity'=>2],
            ]
        ])->postJson(route('checkout.applyPromo'), ['code'=>'SAVE5'])
            ->assertOk()
            ->assertJson([
                'subtotal' => 40.00,   // 20 + (10×2)
                'discount' => 5.00,    // fixed
                'tax'      => 0.0,     // assuming tax_rate=0
                'total'    => 35.00,
            ]);
    }

    /** @test */
    public function place_order_returns_error_when_cart_empty()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('checkout.placeOrder'), [
                'shipping_address' => '123 Lane',
                'email'            => 'a@b.com',
                'phone'            => null,
            ])
            ->assertStatus(422)
            ->assertJson(['error' => 'Your cart is empty.']);
    }

    /** @test */
    public function place_order_creates_order_and_returns_session()
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['price'=>15.50]);

        // define the cart item
        $cartItem = [
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => 15.50,
            'quantity'   => 2,
        ];

        // seed a valid promo
        PromoCode::create([
            'code'       => 'PERC10',
            'type'       => 'percent',
            'discount'   => 10,  // 10%
            'max_uses'   => null,
            'used_count' => 0,
            'expires_at' => now()->addDay(),
            'active'     => true,
        ]);

        $this->actingAs($user)
            ->withSession([
                'cart'  => [$cartItem],
                'promo' => ['code'=>'PERC10','discount'=>3.10], // 10% of 31.00
            ])
            ->postJson(route('checkout.placeOrder'), [
                'shipping_address' => '456 Road Ave',
                'email'            => 'test@ex.com',
                'phone'            => '123-456-7890',
            ])
            ->assertOk()
            ->assertJsonStructure([
                'checkout_session_id',
                'checkout_url',
                'order_id',
            ]);

        // assert order was persisted
        $this->assertDatabaseHas('orders', [
            'user_id'          => $user->id,
            'shipping_address' => '456 Road Ave',
            'email'            => 'test@ex.com',
            'total'            => 31.00 - 3.10, // no tax
            'status'           => 'pending',
        ]);

        // assert the order items were created
        $this->assertDatabaseHas('order_items', [
            'order_id'   => 1,
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => 15.50,
            'total'      => 31.00,
        ]);

        // assert promo use count incremented
        $this->assertDatabaseHas('promo_codes', [
            'code'       => 'PERC10',
            'used_count' => 1,
        ]);
    }
}
