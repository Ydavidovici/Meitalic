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

    public function getRate(array $shipTo, float $weight, array $dims): float
    {
        $res = Http::withBasicAuth($this->cfg['user'], $this->cfg['pass'])
            ->post('https://onlinetools.ups.com/ship/v1/rating/Rate', [
                'UPSSecurity'=>[ /* fill in license + creds */ ],
                'Shipment'=>[
                    'Shipper'=>[ /* your origin */ ],
                    'ShipTo' =>$shipTo,
                    'Package'=>[
                        'PackagingType'=>['Code'=>'02'],
                        'Dimensions'   =>$dims,
                        'PackageWeight'=>[
                            'UnitOfMeasurement'=>['Code'=>'LBS'],
                            'Weight'=>$weight
                        ],
                    ],
                ],
            ]);

        return (float) $res->json(
            'RateResponse.RatedShipment.TotalCharges.MonetaryValue',
            0
        );
    }
}
