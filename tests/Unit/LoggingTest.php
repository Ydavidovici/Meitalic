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
    public function ups_service_get_rate_logs_oauth_and_rate_calls()
    {
        // 0) Provide dummy config so UPSService has what it needs
        config([
            'shipping.ups.client_id'       => 'DUMMY_ID',
            'shipping.ups.client_secret'   => 'DUMMY_SECRET',
            'shipping.ups.oauth_token_url' => 'https://oauth.example/token',
            'shipping.ups.rate_endpoint'   => 'https://rate.example/ship',
            'shipping.shipper_address'     => [
                'AddressLine'       => '123 Test St',
                'City'              => 'Testville',
                'StateProvinceCode' => 'TS',
                'PostalCode'        => '12345',
                'CountryCode'       => 'US',
            ],
        ]);

        // 1) Prepare a MockHandler with two sequential responses
        $mock = new MockHandler([
            new GuzzleResponse(200, ['Content-Type'=>'application/json'], json_encode(['access_token'=>'TEST_TOKEN'])),
            new GuzzleResponse(200, ['Content-Type'=>'application/json'], json_encode([
                'RateResponse' => [
                    'RatedShipment' => [
                        ['TotalCharges'=>['MonetaryValue'=>'42.50']]
                    ]
                ]
            ])),
        ]);
        $stack = HandlerStack::create($mock);
        // push your logging middleware onto that same stack
        $stack->push(new GuzzleLogMiddleware('api'));

        // 2) Override the withLogging() macro to use our mocked stack
        Http::macro('withLogging', function () use ($stack) {
            return Http::withOptions(['handler' => $stack]);
        });

        // 3) Invoke the service
        $svc  = new UPSService();
        $rate = $svc->getRate(
            ['AddressLine' => '123 Test St'],
            10.5,
            ['length'=>5,'width'=>4,'height'=>3]
        );

        $this->assertEquals(42.5, $rate);

        // 4) Ensure two calls were logged: one for OAuth, one for Rate
        $log = File::get($this->apiLog);
        $this->assertSame(2, substr_count($log, 'HTTP ▶️ Request'));
        $this->assertSame(2, substr_count($log, 'HTTP ◀️ Response'));
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
