<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ShipStationService;
use Illuminate\Http\Client\RequestException;

class ShippingRatesTest extends TestCase
{
    /**
     * Blast a handful of random packages through ShipStation and print the cheapest rate,
     * catching 500 errors so we can inspect payloads.
     */
    public function test_random_shipstation_rates()
    {
        /** @var ShipStationService $svc */
        $svc = $this->app->make(ShipStationService::class);

        // 1) 'From' address from config
        $from = [
            'postalCode' => config('shipping.shipper_address.postalCode'),
            'country'    => config('shipping.shipper_address.country'),
            'state'      => config('shipping.shipper_address.state') ?? null,
            'city'       => config('shipping.shipper_address.city')  ?? null,
        ];

        $states = ['NY','CA','TX','FL'];
        $runs   = 5;

        for ($i = 1; $i <= $runs; $i++) {
            // 2) random 'To' ZIP/state
            $to = [
                'postalCode' => str_pad(rand(7000, 99999), 5, '0', STR_PAD_LEFT),
                'country'    => 'US',
                'state'      => $states[array_rand($states)],
                'city'       => null,
            ];

            // 3) random package dims & weight
            $parcel = [
                'length' => rand(1, 20),
                'width'  => rand(1, 20),
                'height' => rand(1, 20),
                'weight' => rand(1, 50),
            ];

            try {
                // 4) Call ShipStation
                $rates = $svc->getRates($from, $to, $parcel);
            } catch (RequestException $e) {
                // Dump payload + response body for inspection
                fwrite(STDERR, "\n--- ShipStation ERROR on run #{$i} ---\n");
                fwrite(STDERR, "Payload: " . json_encode(compact('from','to','parcel')) . "\n");
                if ($e->response) {
                    fwrite(STDERR, "HTTP {$e->response->status()} response body: " . $e->response->body() . "\n");
                }
                fwrite(STDERR, str_repeat('-', 40) . "\n");
                continue;
            }

            // 5) Basic assertions
            $this->assertIsArray($rates, 'Rates should come back as an array');
            $this->assertNotEmpty($rates, 'Expected at least one rate');

            // 6) Determine cost field
            $first = reset($rates);
            if (isset($first['shipRate'])) {
                $costKey = 'shipRate';
            } elseif (isset($first['shipmentCost'])) {
                $costKey = 'shipmentCost';
            } else {
                fwrite(STDERR, "Unknown cost key, sample rate: " . json_encode($first) . "\n");
                $this->fail('Rates array does not contain shipRate or shipmentCost');
            }

            // 7) Find the cheapest rate
            $cheapest = collect($rates)
                ->sortBy(fn($r) => $r[$costKey] ?? INF)
                ->first();

            $this->assertNotNull($cheapest, 'Could not determine a cheapest rate');
            $this->assertIsNumeric($cheapest[$costKey], "$costKey should be numeric");
            $this->assertGreaterThan(0, $cheapest[$costKey], "$costKey must be > 0");

            // 8) Dump successful run
            fwrite(STDERR, "\nRun #{$i}\n");
            fwrite(STDERR, " To ZIP: {$to['postalCode']} | Dims: {$parcel['length']}×{$parcel['width']}×{$parcel['height']}″ | Weight: {$parcel['weight']} lb\n");
            fwrite(STDERR, " → Cheapest: \${$cheapest[$costKey]} ({$cheapest['serviceCode']})\n");
            fwrite(STDERR, str_repeat('─', 40) . "\n");
        }
    }

    /**
     * Inspect the rate for our actual product box:
     *   7.52 lb, 18.67″ × 11.96″ × 9.09″
     */
    // this is an old test for a specific product, wich now works
    public function test_real_product_dims_rate()
    {
        /** @var ShipStationService $svc */
        $svc = $this->app->make(ShipStationService::class);

        $from = [
            'postalCode' => config('shipping.shipper_address.postalCode'),
            'country'    => config('shipping.shipper_address.country'),
            'state'      => config('shipping.shipper_address.state'),
            'city'       => config('shipping.shipper_address.city'),
        ];
        $to = [
            'postalCode' => '07621',
            'country'    => 'US',
            'state'      => 'NJ',
            'city'       => 'Bergenfield',
        ];

        // your real-world package
        $parcel = [
            'length' => 9.0,
            'width'  => 6.5,
            'height' => 3.5,
            'weight' => 7.52,
        ];

        // dump the exact request payload
        $payload = [
            'carrierCode'    => 'ups',
            'serviceCode'    => null,
            'packageCode'    => null,
            'fromPostalCode' => $from['postalCode'],
            'fromCountry'    => $from['country'],
            'fromState'      => $from['state'],
            'fromCity'       => $from['city'],
            'toPostalCode'   => $to['postalCode'],
            'toCountry'      => $to['country'],
            'toState'        => $to['state'],
            'toCity'         => $to['city'],
            'residential'    => false,
            'confirmation'   => 'none',
            'weight'         => ['value' => $parcel['weight'], 'units' => 'pounds'],
            'dimensions'     => [
                'units'  => 'inches',
                'length' => $parcel['length'],
                'width'  => $parcel['width'],
                'height' => $parcel['height'],
            ],
        ];
        fwrite(STDERR, "\n--- REAL PRODUCT PAYLOAD ---\n");
        fwrite(STDERR, json_encode($payload, JSON_PRETTY_PRINT) . "\n");

        $rates = $svc->getRates($from, $to, $parcel, 'ups', null, null, false);

        $this->assertNotEmpty($rates, 'Expected at least one rate back');
        fwrite(STDERR, "\n--- REAL PRODUCT RATES ---\n");
        fwrite(STDERR, json_encode($rates, JSON_PRETTY_PRINT) . "\n");
    }
}
