<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_create_product_page()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get(route('products.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_access_create_product_page()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $response = $this->get(route('products.create'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_a_product()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $data = [
            'name'        => 'Test Product',
            'brand'       => 'Test Brand',
            'description' => 'Test description',
            'price'       => 99.99,
            'image'       => 'http://example.com/image.png',
            'inventory'   => 50,
        ];

        $response = $this->post(route('products.store'), $data);
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'name'  => 'Test Product',
            'brand' => 'Test Brand',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_a_product()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $data = [
            'name'        => 'Test Product',
            'brand'       => 'Test Brand',
            'description' => 'Test description',
            'price'       => 99.99,
            'image'       => 'http://example.com/image.png',
            'inventory'   => 50,
        ];

        $response = $this->post(route('products.store'), $data);
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_edit_product_page()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $product = Product::factory()->create();

        $response = $this->get(route('products.edit', $product));
        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_access_edit_product_page()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $product = Product::factory()->create();

        $response = $this->get(route('products.edit', $product));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_a_product()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $product = Product::factory()->create([
            'name'  => 'Original Name',
            'brand' => 'Original Brand'
        ]);

        $updateData = [
            'name'        => 'Updated Name',
            'brand'       => 'Updated Brand',
            'description' => $product->description,
            'price'       => $product->price,
            'image'       => $product->image,
            'inventory'   => $product->inventory,
        ];

        $response = $this->put(route('products.update', $product), $updateData);
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'Updated Name',
            'brand' => 'Updated Brand',
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_a_product()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $product = Product::factory()->create([
            'name'  => 'Original Name',
            'brand' => 'Original Brand'
        ]);

        $updateData = [
            'name'        => 'Updated Name',
            'brand'       => 'Updated Brand',
            'description' => $product->description,
            'price'       => $product->price,
            'image'       => $product->image,
            'inventory'   => $product->inventory,
        ];

        $response = $this->put(route('products.update', $product), $updateData);
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_a_product()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $product = Product::factory()->create();

        $response = $this->delete(route('products.destroy', $product));
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_delete_a_product()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $product = Product::factory()->create();

        $response = $this->delete(route('products.destroy', $product));
        $response->assertStatus(403);
    }
}
