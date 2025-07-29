<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class ShipStationService
{
    protected array $cfg;

    public function __construct()
    {
        $this->cfg = config('shipping.shipstation');
    }

    /**
     * Generic legacy call: fetch all raw rates for a given carrier code.
     */
    protected function getRates(
        array $from,
        array $to,
        array $parcel,
        string $carrierCode
    ): array {
        $payload = [
            'carrierCode'    => $carrierCode,
            'packageCode'    => 'package',
            'fromPostalCode' => $from['postalCode'],
            'fromCountry'    => $from['country'],
            'fromState'      => $from['state']   ?? null,
            'fromCity'       => $from['city']    ?? null,
            'toPostalCode'   => $to['postalCode'],
            'toCountry'      => $to['country'],
            'toState'        => $to['state']     ?? null,
            'toCity'         => $to['city']      ?? null,
            'residential'    => true,
            'confirmation'   => 'delivery',
            'weight'         => ['value' => $parcel['weight'], 'units' => 'pounds'],
            'dimensions'     => [
                'units'  => 'inches',
                'length' => $parcel['length'],
                'width'  => $parcel['width'],
                'height' => $parcel['height'],
            ],
        ];

        $response = Http::withBasicAuth($this->cfg['key'], $this->cfg['secret'])
            ->acceptJson()
            ->post("{$this->cfg['base']}/shipments/getrates", $payload);

        // If ShipStation returns any 4xx/5xx, capture everything and stop.
            if ($response->failed()) {
                dd([
                    'carrier' => $carrierCode,
                    'status' => $response->status(),
                    'payload' => $payload,
                    // ← raw body, not the json() helper
                    'raw_body' => $response->body(),
                ]);
            }

        // Otherwise, throw on any other issues and return the data
        $response->throw();
        return $response->json();
    }

    /**
     * Apply delivery-day estimation and pick cheapest per day.
     */
    protected function filterCheapestByDay(array $allRates): array
    {
        $best = [];
        foreach ($allRates as $rate) {
            $days = $this->parseDeliveryDays($rate);
            if ($days === null) continue;

            $cost = $rate['shipmentCost'] + $rate['otherCost'];
            if (!isset($best[$days]) || $cost < ($best[$days]['shipmentCost'] + $best[$days]['otherCost'])) {
                $rate['deliveryDays'] = $days;
                $best[$days] = $rate;
            }
        }
        ksort($best);
        return array_values($best);
    }

    /**
     * Estimate delivery days from serviceCode or serviceName.
     */
    protected function parseDeliveryDays(array $rate): ?int
    {
        $code = strtolower($rate['serviceCode']  ?? '');
        $name = strtolower($rate['serviceName']  ?? '');
        $map  = [
            // USPS
            'priority_mail_express' => 1,
            'priority_mail'         => 2,
            'media_mail'            => 5,
            'parcel_select'         => 3,
            'ground_advantage'      => 3,
            // UPS
            'next_day'              => 1,
            '2nd_day'               => 2,
            '3_day'                 => 3,
            // FedEx
            'overnight'             => 1,
            '2day'                  => 2,
            'express_saver'         => 3,
            'ground'                => 5,
            'ground_economy'        => 5,
        ];

        foreach ($map as $pattern => $days) {
            if (str_contains($code, $pattern) || str_contains($name, $pattern)) {
                return $days;
            }
        }

        return null;
    }

    // ——— Carrier‐specific public methods ———

    /**
     * Get USPS (Stamps.com) rates.
     */
    public function getUspsRates(array $from, array $to, array $parcel): array
    {
        $raw = $this->getRates($from, $to, $parcel, 'stamps_com');
        return $this->filterCheapestByDay($raw);
    }

    /**
     * Get UPS rates. Not used, returns error.
     */
    public function getUpsRates(array $from, array $to, array $parcel): array
    {
        $raw = $this->getRates($from, $to, $parcel, 'ups');
        return $this->filterCheapestByDay($raw);
    }

    /**
     * Get DHL Express Worldwide rates. Not used, returns error.
     */
    public function getDhlRates(array $from, array $to, array $parcel): array
    {
        $raw = $this->getRates($from, $to, $parcel, 'dhl_express_worldwide');
        return $this->filterCheapestByDay($raw);
    }

    /**
     * Get FedEx rates.
     */
    public function getFedexRates(array $from, array $to, array $parcel): array
    {
        $raw = $this->getRates($from, $to, $parcel, 'fedex_walleted');
        return $this->filterCheapestByDay($raw);
    }

    /**
     * Get SEKO LTL rates. Not used, returns error.
     */
    public function getSekoRates(array $from, array $to, array $parcel): array
    {
        $raw = $this->getRates($from, $to, $parcel, 'seko_ltl_walleted');
        return $this->filterCheapestByDay($raw);
    }

    /**
     * Get GlobalPost rates. Not used, returns error.
     */
    public function getGlobalPostRates(array $from, array $to, array $parcel): array
    {
        $raw = $this->getRates($from, $to, $parcel, 'globalpost');
        return $this->filterCheapestByDay($raw);
    }

    /**
     * Aggregate all carrier rates and pick cheapest per day across carriers.
     */
    public function getAllRates(array $from, array $to, array $parcel): array
    {
        $all = [];
        $all = array_merge($all, $this->getUspsRates($from, $to, $parcel));
        $all = array_merge($all, $this->getUpsRates($from, $to, $parcel));
        $all = array_merge($all, $this->getFedexRates($from, $to, $parcel));

        return $this->filterCheapestByDay($all);
    }
}
