<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function empty_cart_redirects_to_cart_index_with_error()
    {
        $response = $this->get('/checkout');
        $response->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', 'Your cart is empty.');
    }
}
