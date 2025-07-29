<?php

namespace App\Services;

use Illuminate\Support\Str;
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

        // **If it's international, tack on customs & duties info:**
        if (strtoupper($from['country']) !== strtoupper($to['country'])) {
            $payload['internationalOptions'] = [
                'billDutiesTo' => 'sender',      // or 'receiver'
                //'nonMachinable' => false,     // optional
            ];

            // You must provide at least one customs item:
            $payload['customsItems'] = [[
                'description'       => 'Merchandise',
                'quantity'          => 1,
                'value'             => $parcel['value']  ?? 0,
                'weight'            => ['value' => $parcel['weight'], 'units' => 'pounds'],
                'countryOfOrigin'   => $from['country'],  // e.g. 'US'
            ]];
        }

        $response = Http::withBasicAuth($this->cfg['key'], $this->cfg['secret'])
            ->acceptJson()
            ->post("{$this->cfg['base']}/shipments/getrates", $payload);

        if ($response->failed()) {
            Log::error('ShipStation rate error', [
                'carrier'  => $carrierCode,
                'status'   => $response->status(),
                'payload'  => $payload,
                'raw_body' => $response->body(),
            ]);
            return [];
        }

        $response->throw();
        return $response->json();
    }


    /**
     * Pick exactly one (cheapest) rate per delivery-day.
     */
    protected function filterCheapestByDay(array $allRates): array
    {
        $best = [];

        foreach ($allRates as $rate) {
            $days = $this->parseDeliveryDays($rate);
            if ($days === null) {
                continue;  // drop everything we can’t map to a day
            }

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
     * Keep cheapest-per-day AND also show anything we couldn’t parse.
     */
    protected function filterAndIncludeUnknown(array $allRates): array
    {
        // 1) get your day-based rates
        $filtered = $this->filterCheapestByDay($allRates);

        // 2) grab the ones you dropped
        $unknown = array_filter($allRates, function($rate) {
            return $this->parseDeliveryDays($rate) === null;
        });

        // 3) merge them on the end
        return array_merge($filtered, array_values($unknown));
    }

    protected function parseDeliveryDays(array $rate): ?int
    {
        $code = strtolower($rate['serviceCode']  ?? '');
        $name = strtolower($rate['serviceName']  ?? '');
        $map  = [
            // ——— USPS ———
            'priority_mail_express'               => 1,
            'priority_mail'                       => 2,
            'media_mail'                          => 5,
            'parcel_select'                       => 3,
            'ground_advantage'                    => 3,

            // ——— UPS Domestic ———
            'next_day'                            => 1,
            '2nd_day'                             => 2,
            '3_day'                               => 3,

            // ——— UPS International ———
            'ups_worldwide_express_plus'          => 1,
            'ups_worldwide_express'               => 1,
            'ups_worldwide_expedited'             => 3,
            'ups_worldwide_saver'                 => 3,

            // ——— FedEx International ———
            'fedex_international_priority'        => 2,
            'fedex_international_economy'         => 5,

            // ——— FedEx Domestic ———
            'overnight'                           => 1,
            '2day'                                => 2,
            'express_saver'                       => 3,
            'ground'                              => 5,
            'ground_economy'                      => 5,
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

        return $this->filterAndIncludeUnknown($all);

    }

    public function createShipment(
         array  $from,
         array  $to,
         array  $parcel,
         string $carrierCode,
         string $serviceCode,
         string $orderNumber,
         array  $orderItems
    ): array {
        $payload = [
            'orderNumber' => $orderNumber,
            'orderKey'    => Str::uuid(),
            'orderDate'   => now()->toIso8601String(),
            'items'       => $orderItems,
            'shipDate' => now()->toDateString(),
            'shipFrom'    => $from,
            'shipTo'      => $to,
            'carrierCode' => $carrierCode,
            'orderStatus' => 'awaiting_shipment',
            'billTo'      => $to,
            'serviceCode' => $serviceCode,
            'packageCode' => 'package',
            'confirmation'=> 'delivery',
            'weight'      => ['value' => $parcel['weight'], 'units' => 'pounds'],
            'dimensions'  => [
                'units'  => 'inches',
                'length' => $parcel['length'],
                'width'  => $parcel['width'],
                'height' => $parcel['height'],
            ],
        ];

        // --- debug dump: what we’re sending
        fwrite(STDOUT, PHP_EOL . "=== createLabel payload ===" . PHP_EOL);
        fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL);

        $resp = Http::withBasicAuth($this->cfg['key'], $this->cfg['secret'])
            ->acceptJson()
            ->post("{$this->cfg['base']}/orders/createorder", $payload);
        // --- debug dump: raw response body
        fwrite(STDOUT, PHP_EOL . "=== createLabel response body ===" . PHP_EOL);
        fwrite(STDOUT, $resp->body() . PHP_EOL);

        if ($resp->failed()) {
            Log::error('ShipStation createShipment error', [
                'payload'  => $payload,
                'response' => $resp->body(),
            ]);
            throw new \Exception('Failed to create shipment in ShipStation');
        }

        return $resp->json(); // returns at least orderId (ShipStation’s internal), etc.
    }



    public function createLabel(
        array  $from,
        array  $to,
        array  $parcel,
        string $carrierCode,
        string $serviceCode,
        int    $shipStationOrderId,
        array  $orderItems
    ): array {
        $payload = [
                        'orderId'      => $shipStationOrderId,
                        'carrierCode'  => $carrierCode,
                        'serviceCode'  => $serviceCode,
                        'packageCode'  => 'package',
                        'confirmation' => 'delivery',
                        'shipDate'     => now()->toDateString(),

                        // required address blocks:
                        'shipFrom'     => $from,
                        'shipTo'       => $to,

                        // required package details:
                        'weight'       => [
                                'value' => $parcel['weight'],
                                'units' => 'pounds',
                            ],

                      // dimensions are optional but recommended:
                        'dimensions'   => [
                                'units'  => 'inches',
                                'length' => $parcel['length'],
                                'width'  => $parcel['width'],
                                'height' => $parcel['height'],
                            ],
                    ];

        // (you can remove these in production, but let’s log them while we debug)
        fwrite(STDOUT, "=== createLabel payload ===\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n");

        $response = Http::withBasicAuth($this->cfg['key'], $this->cfg['secret'])
            ->acceptJson()
            ->post("{$this->cfg['base']}/shipments/createlabel", $payload);

        fwrite(STDOUT, "=== createLabel response ===\n" . $response->body() . "\n");

        if ($response->failed()) {
            Log::error('ShipStation createLabel error', [
                'payload'  => $payload,
                'response' => $response->body(),
            ]);
            throw new \Exception('Failed to create shipping label.');
        }

// ShipStation returns "shipmentId" rather than "labelId" — tests expect labelId
        $body = $response->json();
        if (!isset($body['labelId']) && isset($body['shipmentId'])) {
            $body['labelId'] = $body['shipmentId'];
        }
        return $body;    }
}
