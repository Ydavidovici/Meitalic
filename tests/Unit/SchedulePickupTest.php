<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\SchedulePickup;
use App\Models\Shipment;
use App\Models\Pickup;
use App\Services\ShipStationService;
use Carbon\Carbon;
use Mockery;

class SchedulePickupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure that SchedulePickup handles multiple carriers/services correctly,
     * grouping shipments and creating one pickup per group.
     */
    public function test_handle_schedules_pickups_grouped_by_carrier_and_service()
    {
        // Freeze "today"
        Carbon::setTestNow(Carbon::parse('2025-07-29 10:00:00'));

        // Create some shipments for today (two carriers)
        Shipment::factory()->create([
            'label_id'     => 'L1',
            'carrier_code' => 'ups',
            'service_code' => 'ups_ground',
            'created_at'   => now(),
        ]);

        Shipment::factory()->create([
            'label_id'     => 'L2',
            'carrier_code' => 'usps',
            'service_code' => 'usps_priority_mail',
            'created_at'   => now(),
        ]);

        // Old shipment (yesterday) should be ignored
        Shipment::factory()->create([
            'label_id'     => 'OLD',
            'carrier_code' => 'ups',
            'service_code' => 'ups_ground',
            'created_at'   => now()->subDay(),
        ]);

        // Create a mock ShipStationService
        $svc = Mockery::mock(ShipStationService::class);

        // Expect createPickup twice: once for ups, once for usps
        $svc->shouldReceive('createPickup')
            ->once()
            ->with(Mockery::on(function($payload) {
                return $payload['carrierCode'] === 'ups'
                    && $payload['serviceCode'] === 'ups_ground'
                    && in_array('L1', $payload['labelIds'])
                    && $payload['shipDate'] === now()->addDay()->toDateString();
            }))
            ->andReturn(['confirmationNumber' => 'CN_UPS']);

        $svc->shouldReceive('createPickup')
            ->once()
            ->with(Mockery::on(function($payload) {
                return $payload['carrierCode'] === 'usps'
                    && $payload['serviceCode'] === 'usps_priority_mail'
                    && in_array('L2', $payload['labelIds']);
            }))
            ->andReturn(['confirmationNumber' => 'CN_USPS']);

        // Bind mock into service container
        $this->app->instance(ShipStationService::class, $svc);

        // Run the job
        (new SchedulePickup)->handle($svc);

        // Assert two pickups recorded
        $pickupDate = now()->addDay()->toDateString();
        $this->assertDatabaseHas('pickups', [
            'pickup_date'         => $pickupDate,
            'confirmation_number' => 'CN_UPS',
        ]);
        $this->assertDatabaseHas('pickups', [
            'pickup_date'         => $pickupDate,
            'confirmation_number' => 'CN_USPS',
        ]);
    }
}
