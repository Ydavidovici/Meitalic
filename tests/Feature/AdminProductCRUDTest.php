<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_update_existing_product()
    {
        // Arrange: make a product
        $product = Product::factory()->create([
            'name'        => 'Iusto fugit inventore',
            'brand'       => 'OrigBrand',
            'category'    => 'OrigCat',
            'description' => 'OrigDesc',
            'price'       => 10.00,
            'inventory'   => 5,
        ]);

        // Act: update it
        $response = $this
            ->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name'        => 'Updated Name',
                'brand'       => 'NewBrand',
                'category'    => 'NewCat',
                'description' => 'Updated description',
                'price'       => 20.50,
                'inventory'   => 12,
            ]);

        // Assert: redirected back to admin dashboard
        $response->assertRedirect(route('admin.dashboard'));

        // And database has new values
        $this->assertDatabaseHas('products', [
            'id'          => $product->id,
            'name'        => 'Updated Name',
            'brand'       => 'NewBrand',
            'category'    => 'NewCat',
            'description' => 'Updated description',
            'price'       => 20.50,
            'inventory'   => 12,
        ]);
    }

    public function test_admin_can_create_and_delete_product()
    {
        // Act: create a new product
        $create = $this
            ->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'name'        => 'Test Product',
                'brand'       => 'BrandX',
                'category'    => 'CatX',
                'description' => 'A test product',
                'price'       => 15.75,
                'inventory'   => 3,
            ]);

        // Assert: redirected back to admin dashboard
        $create->assertRedirect(route('admin.dashboard'));

        // Assert: it exists
        $prod = Product::where('name', 'Test Product')->first();
        $this->assertNotNull($prod);

        // Act: delete it
        $delete = $this
            ->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $prod));

        // Assert: redirected back to admin dashboard
        $delete->assertRedirect(route('admin.dashboard'));

        // Assert: it's gone
        $this->assertDatabaseMissing('products', ['id' => $prod->id]);
    }
}
