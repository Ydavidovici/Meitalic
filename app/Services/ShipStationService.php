<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class ShipStationService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('shipping');
    }

    /**
     * Legacy: return *all* rates from ShipStation for one carrier.
     */
    public function getRatesLegacy(
        array  $from,
        array  $to,
        array  $parcel,
        string $carrier,
        ?string $service     = null,
        ?string $packageCode = 'package',
        bool   $residential  = false
    ): array {
        $payload = [
            'carrierCode'    => $carrier,
            'serviceCode'    => $service,
            'packageCode'    => $packageCode,
            'fromPostalCode' => $from['postalCode'],
            'fromCountry'    => $from['country'],
            'fromState'      => $from['state'] ?? null,
            'fromCity'       => $from['city']  ?? null,
            'toPostalCode'   => $to['postalCode'],
            'toCountry'      => $to['country'],
            'toState'        => $to['state']   ?? null,
            'toCity'         => $to['city']    ?? null,
            'residential'    => $residential,
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

        $resp = Http::withBasicAuth(
            $this->cfg['shipstation']['key'],
            $this->cfg['shipstation']['secret']
        )
            ->acceptJson()
            ->post("{$this->cfg['shipstation']['base']}/shipments/getrates", $payload)
            ->throw();

        return $resp->json();
    }

    /**
     * New: poll all configured carriers, skip invalid ones,
     * merge & pick cheapest per delivery-day.
     */
    public function getRates(
        array           $from,
        array           $to,
        array           $parcel,
        string|array    $carrier      = [],
        ?string         $service      = null,
        ?string         $packageCode  = 'package',
        bool            $residential  = false
    ): array {
        $carriers = is_array($carrier) && count($carrier)
            ? $carrier
            : $this->cfg['shipstation']['carriers'];

        $allRates = [];

        foreach ($carriers as $code) {
            try {
                $legs = $this->getRatesLegacy(
                    $from, $to, $parcel,
                    $code, $service, $packageCode, $residential
                );
                $allRates = array_merge($allRates, $legs);

            } catch (RequestException $e) {
                $body = $e->response?->json() ?? [];

                // if it's an invalid-carrier error, skip silently
                if (isset($body['ExceptionMessage'])
                    && str_contains($body['ExceptionMessage'], 'Invalid carrierCode')
                ) {
                    continue;
                }

                // otherwise rethrow—something else went wrong.
                throw $e;
            }
        }

        return $this->filterCheapestByDay($allRates);
    }

    protected function filterCheapestByDay(array $allRates): array
    {
        $best = [];
        foreach ($allRates as $r) {
            $days = $this->parseDeliveryDays($r);
            if ($days === null) {
                continue;
            }
            $cost = $r['shipmentCost'] + $r['otherCost'];
            if (!isset($best[$days]) ||
                $cost < ($best[$days]['shipmentCost'] + $best[$days]['otherCost'])
            ) {
                $r['deliveryDays'] = $days;
                $best[$days] = $r;
            }
        }
        ksort($best);
        return array_values($best);
    }

    protected function parseDeliveryDays(array $rate): ?int
    {
        if (isset($rate['deliveryDays'])) {
            return (int)$rate['deliveryDays'];
        }
        $code = strtolower($rate['serviceCode'] ?? '');
        $name = strtolower($rate['serviceName'] ?? '');

        if (str_contains($code, 'next_day')   || str_contains($name, 'next day'))   return 1;
        if (str_contains($code, '2nd_day')    || str_contains($name, '2 day'))     return 2;
        if (str_contains($code, '3_day')      || str_contains($name, '3 day'))     return 3;
        // …add more as needed
        return null;
    }
}
