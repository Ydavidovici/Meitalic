<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UPSService
{
    protected $cfg;

    public function __construct()
    {
        $this->cfg = config('shipping.ups');
    }

    /**
     * Query UPS Rate API.
     *
     * @param  array  $shipTo   ['AddressLine'=>'…','City'=>'…','StateProvinceCode'=>'…','PostalCode'=>'…','CountryCode'=>'…']
     * @param  float  $weight   total lbs
     * @param  array  $dims     ['length'=>int,'width'=>int,'height'=>int]
     * @return float            monetary value
     */
    public function getRate(array $shipTo, float $weight, array $dims): float
    {
        $payload = [
            'UPSSecurity' => [
                'UsernameToken' => [
                    'Username' => $this->cfg['user'],
                    'Password' => $this->cfg['pass'],
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $this->cfg['license'],
                ],
            ],
            'RateRequest' => [
                'Request' => [
                    'RequestOption' => 'Rate',
                ],
                'Shipment' => [
                    'Shipper' => [
                        'Address' => [
                            // your warehouse/origin address
                            'AddressLine'       => config('app.shipper_address.line', ''),
                            'City'              => config('app.shipper_address.city', ''),
                            'StateProvinceCode' => config('app.shipper_address.state', ''),
                            'PostalCode'        => config('app.shipper_address.postal', ''),
                            'CountryCode'       => config('app.shipper_address.country', 'US'),
                        ],
                        'ShipperNumber' => $this->cfg['account'],
                    ],
                    'ShipTo'   => ['Address' => $shipTo],
                    'ShipFrom' => [
                        'Address' => [
                            'AddressLine'       => config('app.shipper_address.line', ''),
                            'City'              => config('app.shipper_address.city', ''),
                            'StateProvinceCode' => config('app.shipper_address.state', ''),
                            'PostalCode'        => config('app.shipper_address.postal', ''),
                            'CountryCode'       => config('app.shipper_address.country', 'US'),
                        ],
                    ],
                    'Package' => [
                        'PackagingType' => ['Code' => '02'],
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
            ],
        ];

        $response = Http::withBasicAuth($this->cfg['user'], $this->cfg['pass'])
            ->post('https://onlinetools.ups.com/ship/v1/rating/Rate', $payload);

        // navigate to the first rated shipment
        return (float) $response->json(
            'RateResponse.RatedShipment.0.TotalCharges.MonetaryValue',
            0
        );
    }
}
