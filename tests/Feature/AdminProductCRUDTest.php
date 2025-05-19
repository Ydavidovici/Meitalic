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
        // Arrange: make a product with all required dimensions
        $product = Product::factory()->create([
            'name'        => 'Iusto fugit inventore',
            'brand'       => 'OrigBrand',
            'category'    => 'OrigCat',
            'description' => 'OrigDesc',
            'price'       => 10.00,
            'inventory'   => 5,
            'weight'      => 1.0,
            'length'      => 10,
            'width'       => 5,
            'height'      => 2,
        ]);

        // Act: update it with new dimension values
        $response = $this
            ->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name'        => 'Updated Name',
                'brand'       => 'NewBrand',
                'category'    => 'NewCat',
                'description' => 'Updated description',
                'price'       => 20.50,
                'inventory'   => 12,
                'weight'      => 2.5,
                'length'      => 12,
                'width'       => 6,
                'height'      => 3,
            ]);

        // Assert: redirected back to admin dashboard
        $response->assertRedirect(route('admin.dashboard'));

        // And database has new values including dimensions
        $this->assertDatabaseHas('products', [
            'id'          => $product->id,
            'name'        => 'Updated Name',
            'brand'       => 'NewBrand',
            'category'    => 'NewCat',
            'description' => 'Updated description',
            'price'       => 20.50,
            'inventory'   => 12,
            'weight'      => 2.5,
            'length'      => 12,
            'width'       => 6,
            'height'      => 3,
        ]);
    }

    public function test_admin_can_create_and_delete_product()
    {
        // Act: create a new product with dimensions
        $create = $this
            ->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'name'        => 'Test Product',
                'brand'       => 'BrandX',
                'category'    => 'CatX',
                'description' => 'A test product',
                'price'       => 15.75,
                'inventory'   => 3,
                'weight'      => 1.2,
                'length'      => 8,
                'width'       => 4,
                'height'      => 2,
            ]);

        // Assert: redirected back to admin dashboard
        $create->assertRedirect(route('admin.dashboard'));

        // Assert: it exists with correct dimensions
        $prod = Product::where('name', 'Test Product')->first();
        $this->assertNotNull($prod);
        $this->assertDatabaseHas('products', [
            'id'     => $prod->id,
            'weight' => 1.2,
            'length' => 8,
            'width'  => 4,
            'height' => 2,
        ]);

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
