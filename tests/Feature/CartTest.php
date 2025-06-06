<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\CartItem;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_can_add_product_to_cart()
    {
        $product = Product::factory()->create();
        $response = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
    }

    /** @test */
    public function guest_can_update_cart_item_quantity()
    {
        $product = Product::factory()->create();
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $item = CartItem::first();
        $response = $this->patch(route('cart.update', $item->id), [
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'id'       => $item->id,
            'quantity' => 5,
        ]);
    }

    /** @test */
    public function guest_can_remove_cart_item()
    {
        $product = Product::factory()->create();
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $item = CartItem::first();
        $response = $this->delete(route('cart.remove', $item->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    /** @test */
    public function guest_can_view_cart_page()
    {
        // Create product
        $product = Product::factory()->create(['price' => 20]);

        // Simulate session (important to simulate guest cart)
        $sessionId = (string) \Illuminate\Support\Str::uuid();
        $this->withSession(['cart_session_id' => $sessionId]);

        // Create cart and CartItem manually
        $cart = \App\Models\Cart::create(['session_id' => $sessionId]);
        \App\Models\CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => $product->price,
            'total'      => $product->price * 2,
        ]);

        // GET the cart page
        $response = $this->get(route('cart.index'));

        // Assert status and view
        $response->assertStatus(200)
            ->assertViewIs('cart.index')
            ->assertSee($product->name);
    }

}
