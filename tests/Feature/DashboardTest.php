<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_is_redirected_from_user_dashboard()
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    /** @test */
    public function regular_user_sees_their_dashboard_and_all_metrics()
    {
        // create a non-admin user
        $user = User::factory()->create([
            'is_admin' => false,
            'password' => Hash::make('secret'),
        ]);

        // create 3 orders with a known total of $20 each
        Order::factory()->count(3)->create([
            'user_id'    => $user->id,
            'total'      => 20.00,
            'created_at' => now(),
        ]);

        // seed a couple of products (active by default)
        Product::factory()->count(2)->create();

        // one active promo code
        PromoCode::factory()->create([
            'active'     => true,
            'code'       => 'SAVE10',
            'expires_at' => now()->addDays(10),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertViewIs('pages.dashboard.index')
            ->assertViewHasAll([
                'totalOrders',
                'yearlySpend',
                'storeCredit',
                'recentOrders',
                'allOrders',
                'recommendations',
                'activePromos',
            ])
            // numeric metrics
            ->assertViewHas('totalOrders', 3)
            ->assertViewHas('yearlySpend', 60.00)
            ->assertViewHas('storeCredit', 0)
            // headings & content
            ->assertSeeText('Total Orders')
            ->assertSeeText('Spend This Year')
            ->assertSeeText('Store Credit')
            ->assertSeeText('You Might Like')
            ->assertSeeText('Active Promo Codes')
            ->assertSeeText('SAVE10')
            // no admin UI
            ->assertDontSee('Manage Products')
            ->assertDontSee('Revenue Today');
    }

    /** @test */
    public function non_admin_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'password' => Hash::make('secret'),
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }

    /** @test */
    public function admin_sees_admin_dashboard_with_all_kpis_and_collections()
    {
        // create an admin user
        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => Hash::make('secret'),
        ]);

        // seed 5 orders all created today
        Order::factory()->count(5)->create([
            'total'      => 50.00,
            'created_at' => now(),
        ]);

        // inventory alerts
        Config::set('inventory.low_stock_threshold', 5);
        Product::factory()->create(['name' => 'LowProd',  'inventory' => 3]);
        Product::factory()->create(['name' => 'ZeroProd', 'inventory' => 0]);
        Product::factory()->count(2)->create(['inventory' => 10]);

        // seed promo codes
        PromoCode::factory()->create([
            'active'     => true,
            'code'       => 'SAVE20',
            'expires_at' => now()->addDays(30),
        ]);
        PromoCode::factory()->create([
            'active'     => true,
            'code'       => 'LIMITED',
            'expires_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200)
            ->assertViewIs('pages.admin.dashboard')
            ->assertViewHasAll([
                'kpis',
                'counts',
                'recentOrders',
                'lowStock',
                'outOfStock',
                'topSellers',
                'topRevenue',
                'slowMovers',
                'newCustomersToday',
                'topCustomers',
                'activeCoupons',
                'expiringCoupons',
                'analyticsHtml',
            ])
            ->assertSeeText('Orders Today')
            ->assertSeeText('Revenue Today')
            ->assertSeeText('Avg. Order Value')
            ->assertSeeText('Inventory Alerts')
            ->assertSeeText('LowProd')
            ->assertSeeText('ZeroProd')
            ->assertSeeText('Active Coupons')
            ->assertSeeText('SAVE20')
            ->assertSeeText('Expiring Soon')
            ->assertSeeText('LIMITED')
            ->assertSeeText('Show Dev Metrics');
    }
}
