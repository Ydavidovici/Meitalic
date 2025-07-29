<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ShipStationService;
use ReflectionClass;

class ShipStationServiceTest extends TestCase
{
    protected ShipStationService $svc;
    protected ReflectionClass $refClass;

    protected function setUp(): void
    {
        parent::setUp();

        // load the real creds you've got in .env
        config([
            'shipping.shipstation.base'   => env('SHIPSTATION_API_BASE'),
            'shipping.shipstation.key'    => env('SHIPSTATION_API_KEY'),
            'shipping.shipstation.secret' => env('SHIPSTATION_API_SECRET'),
        ]);

        $this->svc      = new ShipStationService;
        $this->refClass = new \ReflectionClass(ShipStationService::class);
    }

    /**
     * Helper to call protected methods if you ever need them.
     */
    protected function invokeMethod(string $name, array $args = [])
    {
        $m = $this->refClass->getMethod($name);
        $m->setAccessible(true);
        return $m->invokeArgs($this->svc, $args);
    }

    public function testParseDeliveryDays()
    {
        $cases = [
            [['serviceCode' => 'PRIORITY_MAIL_EXPRESS'], 1],
            [['serviceCode' => 'Priority_Mail'],         2],
            [['serviceName' => 'ground_economy'],        5],
            [['serviceCode' => 'completely_unknown'], null],
        ];

        foreach ($cases as [$input, $expected]) {
            $this->assertSame(
                $expected,
                $this->invokeMethod('parseDeliveryDays', [$input]),
                "parseDeliveryDays failed for " . json_encode($input)
            );
        }
    }

    public function testFilterCheapestByDay()
    {
        $raw = [
            ['serviceCode'  => 'next_day', 'shipmentCost' => 10, 'otherCost' => 1],
            ['serviceCode'  => 'next_day', 'shipmentCost' => 12, 'otherCost' => 0],
            ['serviceCode'  => '2day',     'shipmentCost' =>  8, 'otherCost' => 2],
            ['serviceCode'  => '2day',     'shipmentCost' =>  7, 'otherCost' => 5],
        ];

        $filtered = $this->invokeMethod('filterCheapestByDay', [$raw]);

        $this->assertCount(2, $filtered);
        $this->assertEquals(1, $filtered[0]['deliveryDays']);
        $this->assertEquals(11, $filtered[0]['shipmentCost'] + $filtered[0]['otherCost']);
        $this->assertEquals(2, $filtered[1]['deliveryDays']);
        $this->assertEquals(10, $filtered[1]['shipmentCost'] + $filtered[1]['otherCost']);
    }

    public function testGetUspsRatesIntegration()
    {
        if (
            empty(config('shipping.shipstation.base')) ||
            empty(config('shipping.shipstation.key')) ||
            empty(config('shipping.shipstation.secret'))
        ) {
            $this->markTestSkipped('ShipStation API credentials not configured.');
        }

        $from = [
            'postalCode' => '10901', 'country' => 'US',
            'state'      => 'NY',    'city'    => 'Airmont',
        ];
        $to = [
            'postalCode' => '07621', 'country' => 'US',
            'state'      => 'NJ',    'city'    => 'Bergenfield',
        ];
        $parcel = ['weight' => 1, 'length' => 5, 'width' => 5, 'height' => 5];

        $rates = $this->svc->getUspsRates($from, $to, $parcel);

        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates, 'Expected USPS to return at least one rate');

        echo PHP_EOL . "=== getUspsRates ===" . PHP_EOL;
        print_r($rates);
    }

    public function testAllCarrierMethodsIntegration()
    {
        if (
            empty(config('shipping.shipstation.base')) ||
            empty(config('shipping.shipstation.key')) ||
            empty(config('shipping.shipstation.secret'))
        ) {
            $this->markTestSkipped('ShipStation API credentials not configured.');
        }

        $from = [
            'postalCode' => '10901', 'country' => 'US',
            'state'      => 'NY',    'city'    => 'Airmont',
        ];
        $to = [
            'postalCode' => '07621', 'country' => 'US',
            'state'      => 'NJ',    'city'    => 'Bergenfield',
        ];

        $parcel = ['weight' => 1, 'length' => 5, 'width' => 5, 'height' => 5];

        $methods = [
            'getUspsRates',
            'getUpsRates',
            'getFedexRates',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($this->svc, $method),
                "Service is missing public method: $method"
            );

            $rates = $this->svc->$method($from, $to, $parcel);

            $this->assertIsArray($rates, "$method did not return an array");
            $this->assertNotEmpty($rates, "$method returned no rates");

            echo PHP_EOL . "=== {$method} ===" . PHP_EOL;
            print_r($rates);
        }
    }

    /**
     * Inspect raw vs. filtered rates for each carrier.
     */
    public function testRawAndFilteredRatesPerCarrier()
    {
        if (
            empty(config('shipping.shipstation.base')) ||
            empty(config('shipping.shipstation.key')) ||
            empty(config('shipping.shipstation.secret'))
        ) {
            $this->markTestSkipped('ShipStation API credentials not configured.');
        }

        $from = [
            'postalCode' => '10901', 'country' => 'US',
            'state'      => 'NY',    'city'    => 'Airmont',
        ];
        $to = [
            'postalCode' => '65134', 'country' => 'IL',
            'state'      => null,    'city'    => 'Tel Aviv-Yafo',
        ];
        $parcel = [
            'weight' => 5.00,
            'length' => 8.5,
            'width'  => 3.5,
            'height' => 3.5,
            'value'  => 100,  // customs
        ];

        $carriers = [
            'stamps_com'      => 'getUspsRates',
            'ups'             => 'getUpsRates',
            'fedex_walleted'  => 'getFedexRates',
        ];

        foreach ($carriers as $code => $method) {
            // 1) Grab the raw rates array via the protected getRates()
            $raw = $this->invokeMethod('getRates', [
                $from, $to, $parcel, $code
            ]);

            // 2) Run them through filterCheapestByDay()
            $filtered = $this->invokeMethod('filterCheapestByDay', [$raw]);

            // Dump to console so you can inspect
            fwrite(STDOUT, PHP_EOL . "=== RAW rates for {$code} ===\n");
            print_r($raw);
            fwrite(STDOUT, PHP_EOL . "=== FILTERED (cheapest/day) for {$code} ===\n");
            print_r($filtered);

            // Basic assertions so the test actually passes/fails
            $this->assertIsArray($raw,  "Expected raw rates array for {$code}");
            $this->assertIsArray($filtered, "Expected filtered rates array for {$code}");
        }
    }
}