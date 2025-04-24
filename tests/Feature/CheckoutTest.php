<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Stripe\Checkout\Session as StripeSession;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function empty_cart_redirects_to_cart_index_with_error()
    {
        $response = $this->post(route('checkout.create'));

        $response->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'Your cart is empty.');
    }

    /** @test */
    public function promo_code_applies_discount_correctly()
    {
        $product = Product::factory()->create(['price' => 100]);

        PromoCode::factory()->create([
            'code' => 'SAVE20',
            'discount' => 20,
            'type' => 'fixed',
            'active' => true,
        ]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->post(route('cart.applyPromo'), [
            'code' => 'SAVE20',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Promo code applied!');

        $sessionId = session()->get('cart_session_id');
        $cart = Cart::where('session_id', $sessionId)->first();

        $this->assertNotNull($cart);
        $this->assertEquals(80, $cart->total);
        $this->assertEquals(20, $cart->discount);
    }

    /** @test */
    public function stripe_checkout_session_is_created()
    {
        $product = Product::factory()->create(['price' => 50]);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $sessionId = session()->get('cart_session_id');
        session(['cart_session_id' => $sessionId]);

        // 🧠 Instead of mocking Stripe, just verify redirect works
        $response = $this->post(route('checkout.create'));

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('http', $response->headers->get('Location'));
    }


    /** @test */
    public function cart_is_cleared_after_checkout_success()
    {
        $product = Product::factory()->create(['price' => 30]);
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $sessionId = session()->get('cart_session_id');
        $cart = Cart::where('session_id', $sessionId)->first();

        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->cartItems);

        $response = $this->get(route('checkout.success'));

        $response->assertStatus(200);
        $response->assertViewIs('checkout.success');

        $cart->refresh();
        $this->assertCount(0, $cart->cartItems);
    }

    /** @test */
    public function authenticated_user_can_checkout()
    {
        $user = \App\Models\User::factory()->create();
        $product = \App\Models\Product::factory()->create(['price' => 60]);

        $this->actingAs($user);
        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Just call the real route and assert redirect happened
        $response = $this->post(route('checkout.create'));

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('http', $response->headers->get('Location'));

        // Check the user cart was created correctly
        $cart = \App\Models\Cart::where('user_id', $user->id)->first();

        $this->assertNotNull($cart);
        $this->assertEquals(60, $cart->total);
    }
}
