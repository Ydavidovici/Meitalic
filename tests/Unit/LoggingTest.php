<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use App\Services\UPSService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\HandlerStack;
use App\Logging\GuzzleLogMiddleware;

class LoggingTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiLog;
    protected string $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        $date           = now()->format('Y-m-d');
        $this->apiLog   = storage_path("logs/api-{$date}.log");
        $this->auditLog = storage_path("logs/audit-{$date}.log");

        File::ensureDirectoryExists(dirname($this->apiLog));
        File::put($this->apiLog, '');
        File::put($this->auditLog, '');
    }
    /** @test */
    public function http_client_with_logging_appends_request_and_response()
    {
        // 1) Prepare Guzzle MockHandler with a 200 JSON response
        $mock = new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode(['foo'=>'bar']))
        ]);
        $stack = HandlerStack::create($mock);
        // 2) Push your logging middleware onto that stack
        $stack->push(new GuzzleLogMiddleware('api'));

        // 3) Use Laravel's Http client with our Guzzle stack
        $response = Http::withOptions(['handler' => $stack])
            ->withHeaders(['X-Custom' => '123'])
            ->post('https://example.com/test-endpoint', ['a' => 'b']);

        // 4) Assert we got back what the mock returned
        $this->assertTrue($response->successful(), 'Expected mocked response to be successful');
        $this->assertSame(['foo' => 'bar'], $response->json());

        // 5) Now read and assert your daily api log file
        $log = File::get($this->apiLog);
        $this->assertStringContainsString('"method":"POST"', $log);
        $this->assertStringContainsString('"url":"https://example.com/test-endpoint"', $log);
        $this->assertStringContainsString('HTTP ◀️ Response', $log, 'Missing incoming response log');
        $this->assertStringContainsString('"status":200',      $log);
    }

    /** @test */
    public function audit_observer_logs_user_model_events()
    {
        $user = \App\Models\User::factory()->create(['email' => 'audit@test']);
        $this->assertStringContainsString('User created', File::get($this->auditLog));

        $user->name = 'Updated Name';
        $user->save();
        $this->assertStringContainsString('User updated', File::get($this->auditLog));

        $user->delete();
        $this->assertStringContainsString('User deleted', File::get($this->auditLog));
    }

    /** @test */
    public function audit_observer_logs_product_model_events()
    {
        $product = \App\Models\Product::factory()->create(['name' => 'Test Product']);
        $this->assertStringContainsString('Product created', File::get($this->auditLog));

        $product->price = 9.99;
        $product->save();
        $this->assertStringContainsString('Product updated', File::get($this->auditLog));

        $product->delete();
        $this->assertStringContainsString('Product deleted', File::get($this->auditLog));
    }

    /** @test */
    public function audit_observer_logs_order_model_events()
    {
        $user  = \App\Models\User::factory()->create();
        $order = \App\Models\Order::factory()->create([
            'user_id'          => $user->id,
            'total'            => 100.00,
            'status'           => 'pending',
            'shipping_address' => '123 Test St',
        ]);

        $this->assertStringContainsString('Order created', File::get($this->auditLog));

        $order->status = 'shipped';
        $order->save();
        $this->assertStringContainsString('Order updated', File::get($this->auditLog));

        $order->delete();
        $this->assertStringContainsString('Order deleted', File::get($this->auditLog));
    }

    /** @test */
    public function audit_observer_logs_review_model_events()
    {
        // Seed a valid order item
        $user    = \App\Models\User::factory()->create();
        $product = \App\Models\Product::factory()->create();
        $order   = \App\Models\Order::factory()->create(['user_id' => $user->id]);
        $item    = \App\Models\OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
        ]);

        $review = \App\Models\Review::create([
            'order_item_id' => $item->id,
            'user_id'       => $user->id,
            'product_id'    => $product->id,
            'rating'        => 4,
            'body'          => 'Test review',
            'status'        => 'pending',
        ]);
        $this->assertStringContainsString('Review created', File::get($this->auditLog));

        $review->status = 'approved';
        $review->save();
        $this->assertStringContainsString('Review updated', File::get($this->auditLog));

        $review->delete();
        $this->assertStringContainsString('Review deleted', File::get($this->auditLog));
    }

    /** @test */
    public function audit_observer_logs_promocode_model_events()
    {
        $promo = \App\Models\PromoCode::factory()->create([
            'code'       => 'CODE123',
            'discount'   => 10,
            'expires_at' => now()->addDays(7),
            'active'     => true,
        ]);
        $this->assertStringContainsString('PromoCode created', File::get($this->auditLog));

        $promo->discount = 15;
        $promo->save();
        $this->assertStringContainsString('PromoCode updated', File::get($this->auditLog));

        $promo->delete();
        $this->assertStringContainsString('PromoCode deleted', File::get($this->auditLog));
    }
}
