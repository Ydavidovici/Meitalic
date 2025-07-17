<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShipStationService
{
    protected $cfg;

    public function __construct()
    {
        $this->cfg = config('shipping.shipstation');
    }

    /**
     * Query ShipStation for rate quotes.
     *
     * @param  array  $from   ['postalCode'=>'10001','country'=>'US','state'=>'NY','city'=>'New York']
     * @param  array  $to     ['postalCode'=>'90210','country'=>'US','state'=>'CA','city'=>'Beverly Hills']
     * @param  array  $parcel ['length'=>10,'width'=>5,'height'=>4,'weight'=>2]
     * @return array          list of rate objects: ['serviceCode', 'carrierCode', 'serviceName', 'shipRate', …]
     */
    public function getRates(array $from, array $to, array $parcel): array
    {
        $payload = [
            'carrierCode'    => null,         // null = return all available carriers
            'serviceCode'    => null,         // null = return all services
            'packageCode'    => 'package',
            'fromPostalCode' => $from['postalCode'],
            'fromCountry'    => $from['country'],
            'fromState'      => $from['state']   ?? null,
            'fromCity'       => $from['city']    ?? null,
            'toPostalCode'   => $to['postalCode'],
            'toCountry'      => $to['country'],
            'toState'        => $to['state']     ?? null,
            'toCity'         => $to['city']      ?? null,
            'weight'         => [
                'value' => $parcel['weight'],
                'units' => 'pounds',
            ],
            'dimensions'     => [
                'units'  => 'inches',
                'length' => $parcel['length'],
                'width'  => $parcel['width'],
                'height' => $parcel['height'],
            ],
        ];

        $response = Http::withBasicAuth(
            $this->cfg['key'],
            $this->cfg['secret']
        )
            ->acceptJson()
            ->post("{$this->cfg['base']}/shipments/getrates", $payload)
            ->throw();  // let it bubble if something’s wrong

        return $response->json('rates', []);
    }
}
