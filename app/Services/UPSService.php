<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UPSService
{
    protected $cfg;

    public function __construct()
    {
        $this->cfg = config('shipping.ups');
    }

    /**
     * Fetch and cache the UPS OAuth access token.
     *
     * @return string
     */
    protected function getAccessToken(): string
    {
        // Cache token for just under one hour (token TTL is typically 3600s)
        return Cache::remember('ups_oauth_token', 3500, function () {
            $response = Http::withBasicAuth(
                $this->cfg['client_id'],
                $this->cfg['client_secret']
            )
                ->asForm()
                ->post($this->cfg['oauth_token_url'], [
                    'grant_type' => 'client_credentials',
                ]);

            return $response->json('access_token');
        });
    }

    /**
     * Query UPS Rate API via OAuth2 and return the first available rate.
     *
     * @param  array  $shipTo   ['AddressLine'=>'…','City'=>'…','StateProvinceCode'=>'…','PostalCode'=>'…','CountryCode'=>'…']
     * @param  float  $weight   total weight in lbs
     * @param  array  $dims     ['length'=>int,'width'=>int,'height'=>int]
     * @return float            monetary value of the rate
     */
    public function getRate(array $shipTo, float $weight, array $dims): float
    {
        $token = $this->getAccessToken();

        $payload = [
            'Shipment' => [
                'Shipper' => [
                    'ShipperNumber' => $this->cfg['account'],
                    'Address'       => config('shipping.shipper_address'),
                ],
                'ShipTo'   => ['Address' => $shipTo],
                'ShipFrom' => ['Address' => config('shipping.shipper_address')],
                'Package'  => [
                    'PackagingType' => ['Code' => '02'], // Customer Supplied Package
                    'Dimensions'    => [
                        'UnitOfMeasurement' => ['Code' => 'IN'],
                        'Length'            => $dims['length'],
                        'Width'             => $dims['width'],
                        'Height'            => $dims['height'],
                    ],
                    'PackageWeight' => [
                        'UnitOfMeasurement' => ['Code' => 'LBS'],
                        'Weight'            => $weight,
                    ],
                ],
            ],
            'TransactionSrc' => config('app.name', 'app'),
        ];

        $response = Http::withToken($token)
            ->post($this->cfg['rate_endpoint'], $payload);

        return (float) $response->json(
            'RateResponse.RatedShipment.0.TotalCharges.MonetaryValue',
            0
        );
    }
}
