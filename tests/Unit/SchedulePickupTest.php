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

        // Today's shipments for UPS and USPS
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

        // Yesterday's shipment ignored
        Shipment::factory()->create([
            'label_id'     => 'OLD',
            'carrier_code' => 'ups',
            'service_code' => 'ups_ground',
            'created_at'   => now()->subDay(),
        ]);

        // Mock ShipStationService
        $svc = Mockery::mock(ShipStationService::class);

        // Expect UPS pickup
        $svc->shouldReceive('createPickup')
            ->once()
            ->with(Mockery::on(function($payload) {
                return $payload['carrierCode'] === 'ups'
                    && $payload['serviceCode'] === 'ups_ground'
                    && $payload['shipDate'] === now()->addDay()->toDateString()
                    && in_array('L1', $payload['labelIds']);
            }))
            ->andReturn(['confirmationNumber' => 'CN_UPS']);

        // Expect USPS pickup
        $svc->shouldReceive('createPickup')
            ->once()
            ->with(Mockery::on(function($payload) {
                return $payload['carrierCode'] === 'usps'
                    && $payload['serviceCode'] === 'usps_priority_mail'
                    && in_array('L2', $payload['labelIds']);
            }))
            ->andReturn(['confirmationNumber' => 'CN_USPS']);

        // Bind mock
        $this->app->instance(ShipStationService::class, $svc);

        // Run the job
        (new SchedulePickup())->handle($svc);

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

    /**
     * Ensure no pickup is scheduled when there are no shipments today.
     */
    public function test_handle_does_not_schedule_if_no_shipments()
    {
        Carbon::setTestNow(now());

        $svc = Mockery::mock(ShipStationService::class);
        $svc->shouldNotReceive('createPickup');
        $this->app->instance(ShipStationService::class, $svc);

        (new SchedulePickup())->handle($svc);
        $this->assertDatabaseCount('pickups', 0);
    }

    /**
     * Ensure existing pickup for tomorrow prevents scheduling again.
     */
    public function test_handle_skips_when_already_scheduled_for_date()
    {
        Carbon::setTestNow(now());

        $pickupDate = now()->addDay()->toDateString();
        Pickup::factory()->create(['pickup_date' => $pickupDate]);

        Shipment::factory()->create([
            'label_id'     => 'X1',
            'carrier_code' => 'ups',
            'service_code' => 'ups_ground',
            'created_at'   => now(),
        ]);

        $svc = Mockery::mock(ShipStationService::class);
        $svc->shouldNotReceive('createPickup');
        $this->app->instance(ShipStationService::class, $svc);

        (new SchedulePickup())->handle($svc);
        $this->assertDatabaseCount('pickups', 1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }
}