<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function home_page_returns_200()
    {
        $response = $this->get(route('home'));
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

    /** @test */
    public function admin_route_redirects_guest_to_login()
    {
        // When not authenticated, accessing /admin should redirect to login.
        $response = $this->get('/admin');
        $response->assertRedirect('/login'); // or use route('login') if defined
    }

    /** @test */
    public function admin_route_allows_authenticated_user()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

}
