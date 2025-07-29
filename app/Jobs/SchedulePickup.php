<?php

namespace App\Jobs;

use App\Models\Pickup;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\ShipStationService;

class SchedulePickup implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Keep this job unique for 24 hours
     */
    public function uniqueFor()
    {
        return 86400; // seconds in a day
    }

    /**
     * Execute the job: schedule pickups for each carrier/service used today.
     */
    public function handle(ShipStationService $svc)
    {
        $pickupDate = now()->addDay()->toDateString();

        // Prevent scheduling multiple times per date
        if (Pickup::where('pickup_date', $pickupDate)->exists()) {
            return;
        }

        // Fetch today's shipments with label IDs and their carrier/service
        $shipments = Shipment::whereDate('created_at', now()->toDateString())
            ->get(['label_id', 'carrier_code', 'service_code']);

        if ($shipments->isEmpty()) {
            return;
        }

        // Group by carrier+service so each gets its own pickup
        $groups = $shipments->groupBy(function($s) {
            return $s->carrier_code . '|' . $s->service_code;
        });

        foreach ($groups as $key => $group) {
            [$carrier, $service] = explode('|', $key, 2);
            $labelIds = $group->pluck('label_id')->all();

            $payload = [
                'carrierCode'    => $carrier,
                'serviceCode'    => $service,
                'shipDate'       => $pickupDate,
                'readyTime'      => '08:00:00',
                'closeTime'      => '17:00:00',
                'labelIds'       => $labelIds,
                'address'        => config('shipping.shipper_address'),
                'contactDetails' => [
                    'name'  => config('shipping.shipper_address.name'),
                    'phone' => config('shipping.shipper_address.phone'),
                    'email' => config('shipping.shipper_address.email'),
                ],
            ];

            // Schedule the pickup via ShipStation
            $resp = $svc->createPickup($payload);

            // Record the pickup, including which carrier/service
            Pickup::create([
                'pickup_date'         => $pickupDate,
                'confirmation_number' => $resp['confirmationNumber'] ?? null,
                'payload'             => array_merge($payload, ['response' => $resp]),
            ]);
        }
    }
}
