<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShipStationService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('shipping.shipstation');
    }

    /**
     * Query ShipStation for rate quotes.
     *
     * @param  array   $from      ['postalCode'=>'10901','country'=>'US','state'=>'NY','city'=>'Airmont']
     * @param  array   $to        ['postalCode'=>'90210','country'=>'US','state'=>'CA','city'=>'Beverly Hills']
     * @param  array   $parcel    ['length'=>10,'width'=>5,'height'=>4,'weight'=>2]
     * @param  string  $carrier   e.g. 'ups', 'stamps_com'
     * @param  string|null $service specific serviceCode (or null for all)
     * @return array             list of rate objects
     */
    public function getRates(

        array $from,
        array $to,
        array $parcel,
        string $carrier = 'ups',
        ?string $service  = null,
        ?string $packageCode = 'package',
        ?bool   $residential = false
    ): array {
        $payload = [
            'carrierCode'    => $carrier,
            'serviceCode'    => $service,
            'packageCode'    => $packageCode ?? null,
            'fromPostalCode' => $from['postalCode'],
            'fromCountry'    => $from['country'],
            'fromState'      => $from['state'] ?? null,
            'fromCity'       => $from['city']  ?? null,
            'toPostalCode'   => $to['postalCode'],
            'toCountry'      => $to['country'],
            'toState'        => $to['state']   ?? null,
            'toCity'         => $to['city']    ?? null,
            'residential'    => $residential ?? false,
            'confirmation'   => 'none',
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
            ->throw();

        return $response->json();
    }
}
