<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\UPSService;

class UPSServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // override config so we know exactly what credentials get pulled in
        config([
            'shipping.ups.user'    => 'test_user',
            'shipping.ups.pass'    => 'test_pass',
            'shipping.ups.license' => 'LIC123',
            'shipping.ups.account' => 'ACCT456',
            'app.shipper_address'  => [
                'line'    => '131 Spook Rock Rd',
                'city'    => 'Suffern',
                'state'   => 'NY',
                'postal'  => '10901',
                'country' => 'US',
            ],
        ]);
    }

    public function test_getRate_sends_correct_payload_and_parses_response()
    {
        Http::fake([
            'https://onlinetools.ups.com/ship/v1/rating/Rate' => Http::response([
                'RateResponse' => [
                    'RatedShipment' => [
                        ['TotalCharges' => ['MonetaryValue' => '42.21']],
                    ],
                ],
            ], 200),
        ]);

        $svc = new UPSService;

        $shipTo = [
            'AddressLine'       => '123 Lane',
            'City'              => 'Testville',
            'StateProvinceCode' => 'CA',
            'PostalCode'        => '90210',
            'CountryCode'       => 'US',
        ];

        $weight = 5.5;
        $dims   = ['length' => 10, 'width' => 8, 'height' => 4];

        $rate = $svc->getRate($shipTo, $weight, $dims);

        // 1) we got the right numeric rate
        $this->assertSame(42.21, $rate);

        // 2) and the HTTP request payload was correct
        Http::assertSent(function ($request) use ($shipTo, $weight, $dims) {
            $body = $request->data();

            return
                $request->url() === 'https://onlinetools.ups.com/ship/v1/rating/Rate'
                // basic auth:
                && $request->hasHeader('Authorization')
                // credentials in payload:
                && data_get($body, 'UPSSecurity.UsernameToken.Username') === 'test_user'
                && data_get($body, 'UPSSecurity.UsernameToken.Password') === 'test_pass'
                && data_get($body, 'UPSSecurity.ServiceAccessToken.AccessLicenseNumber') === 'LIC123'
                // shipper address from config:
                && data_get($body, 'RateRequest.Shipment.Shipper.Address.AddressLine') === '131 Spook Rock Rd'
                // dynamic ShipTo:
                && data_get($body, 'RateRequest.Shipment.ShipTo.Address.AddressLine') === '123 Lane'
                // dims and weight:
                && data_get($body, 'RateRequest.Shipment.Package.Dimensions.Length') === $dims['length']
                && data_get($body, 'RateRequest.Shipment.Package.PackageWeight.Weight') === $weight;
        });
    }

    public function test_getRate_returns_zero_on_unexpected_response()
    {
        Http::fake([
            'https://onlinetools.ups.com/ship/v1/rating/Rate' => Http::response([], 200),
        ]);

        $svc = new UPSService;
        $rate = $svc->getRate([], 1.0, ['length'=>1,'width'=>1,'height'=>1]);

        $this->assertSame(0.0, $rate, 'Fallback to 0 when JSON path missing');
    }
}
