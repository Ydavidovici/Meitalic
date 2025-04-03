<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function home_page_returns_200()
    {
        $response = $this->get(route('home')); // route('home') should point to your home page
        $response->assertStatus(200);
    }

    /** @test */
    public function contact_page_returns_200()
    {
        $response = $this->get(route('contact'));
        $response->assertStatus(200);
    }

    /** @test */
    public function products_page_returns_200()
    {
        $response = $this->get(route('products.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function brands_page_returns_200()
    {
        $response = $this->get(route('brands.index'));
        $response->assertStatus(200);
    }
}
