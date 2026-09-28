<?php

namespace Tests\Feature;

use App\Enums\DocumentExpiryStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDocumentExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_green_when_more_than_a_month_is_left(): void
    {
        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => now()->addDays(120),
            'insurance_expiry_date' => now()->addDays(90),
        ]);

        $this->assertSame(120, $vehicle->rcDaysRemaining());
        $this->assertSame(DocumentExpiryStatus::Valid, $vehicle->rcExpiryStatus());
        $this->assertSame(DocumentExpiryStatus::Valid, $vehicle->insuranceExpiryStatus());
    }

    public function test_status_is_yellow_within_the_last_month(): void
    {
        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => now()->addDays(20),
            'insurance_expiry_date' => now()->addDays(29),
        ]);

        $this->assertSame(DocumentExpiryStatus::Expiring, $vehicle->rcExpiryStatus());
        $this->assertSame(DocumentExpiryStatus::Expiring, $vehicle->insuranceExpiryStatus());
    }

    public function test_status_is_red_below_seven_days(): void
    {
        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => now()->addDays(6),
            'insurance_expiry_date' => now()->addDay(),
        ]);

        $this->assertSame(6, $vehicle->rcDaysRemaining());
        $this->assertSame(1, $vehicle->insuranceDaysRemaining());
        $this->assertSame(DocumentExpiryStatus::Critical, $vehicle->rcExpiryStatus());
        $this->assertSame(DocumentExpiryStatus::Critical, $vehicle->insuranceExpiryStatus());
    }

    public function test_the_seven_day_boundary_is_still_red_and_eight_is_yellow(): void
    {
        $sevenDays = Vehicle::factory()->create(['rc_expiry_date' => now()->addDays(7)]);
        $eightDays = Vehicle::factory()->create(['rc_expiry_date' => now()->addDays(8)]);

        $this->assertSame(DocumentExpiryStatus::Critical, $sevenDays->rcExpiryStatus());
        $this->assertSame(DocumentExpiryStatus::Expiring, $eightDays->rcExpiryStatus());
    }

    public function test_an_already_lapsed_document_is_expired(): void
    {
        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => now()->subDay(),
            'insurance_expiry_date' => now()->subDays(10),
        ]);

        $this->assertSame(-1, $vehicle->rcDaysRemaining());
        $this->assertSame(DocumentExpiryStatus::Expired, $vehicle->rcExpiryStatus());
        $this->assertSame(DocumentExpiryStatus::Expired, $vehicle->insuranceExpiryStatus());
    }

    public function test_a_vehicle_without_dates_reports_missing_documents(): void
    {
        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => null,
            'insurance_expiry_date' => null,
        ]);

        $this->assertNull($vehicle->rcDaysRemaining());
        $this->assertSame(DocumentExpiryStatus::Missing, $vehicle->rcExpiryStatus());
        $this->assertSame(DocumentExpiryStatus::Missing, $vehicle->documentExpiryStatus());
    }

    public function test_vehicle_reports_the_most_urgent_of_both_documents(): void
    {
        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => now()->addDays(200),
            'insurance_expiry_date' => now()->addDays(3),
        ]);

        $this->assertSame(DocumentExpiryStatus::Critical, $vehicle->documentExpiryStatus());
    }

    public function test_admin_can_store_rc_and_insurance_dates(): void
    {
        $admin = User::factory()->create();
        $vehicle = Vehicle::factory()->make();

        $response = $this->actingAs($admin)->post(route('admin.vehicles.store'), [
            'name' => $vehicle->name,
            'model' => $vehicle->model,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'registration_number' => $vehicle->registration_number,
            'seating_capacity' => $vehicle->seating_capacity,
            'city_id' => $vehicle->city_id,
            'status' => 'Available',
            'rc_issue_date' => '2024-01-10',
            'rc_expiry_date' => '2034-01-10',
            'insurance_issue_date' => '2025-03-01',
            'insurance_expiry_date' => '2026-03-01',
        ]);

        $response->assertSessionHasNoErrors();

        $created = Vehicle::where('registration_number', $vehicle->registration_number)->first();

        $this->assertSame('2024-01-10', $created->rc_issue_date->format('Y-m-d'));
        $this->assertSame('2034-01-10', $created->rc_expiry_date->format('Y-m-d'));
        $this->assertSame('2025-03-01', $created->insurance_issue_date->format('Y-m-d'));
        $this->assertSame('2026-03-01', $created->insurance_expiry_date->format('Y-m-d'));
    }

    public function test_admin_can_update_the_document_dates(): void
    {
        $admin = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.vehicles.update', $vehicle), [
            'name' => $vehicle->name,
            'model' => $vehicle->model,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'registration_number' => $vehicle->registration_number,
            'seating_capacity' => $vehicle->seating_capacity,
            'city_id' => $vehicle->city_id,
            'status' => $vehicle->status,
            'rc_expiry_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(
            DocumentExpiryStatus::Critical,
            $vehicle->fresh()->rcExpiryStatus()
        );
    }

    public function test_an_expiry_date_before_its_issue_date_is_rejected(): void
    {
        $admin = User::factory()->create();
        $vehicle = Vehicle::factory()->make();

        $response = $this->actingAs($admin)->post(route('admin.vehicles.store'), [
            'name' => $vehicle->name,
            'model' => $vehicle->model,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'registration_number' => $vehicle->registration_number,
            'seating_capacity' => $vehicle->seating_capacity,
            'city_id' => $vehicle->city_id,
            'status' => 'Available',
            'rc_issue_date' => '2026-01-10',
            'rc_expiry_date' => '2025-01-10',
        ]);

        $response->assertSessionHasErrors('rc_expiry_date');
    }

    public function test_fleet_pages_show_the_remaining_days(): void
    {
        $admin = User::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => now()->addDays(45),
            'insurance_expiry_date' => now()->addDays(4),
        ]);

        $this->actingAs($admin)->get(route('admin.vehicles.index'))
            ->assertStatus(200)
            ->assertSee('45 days left')
            ->assertSee('4 days left');

        $this->actingAs($admin)->get(route('admin.vehicles.show', $vehicle))
            ->assertStatus(200)
            ->assertSee('Registration Certificate (RC)')
            ->assertSee('45 days left')
            ->assertSee('4 days left');
    }

    public function test_fleet_pages_report_missing_documents_gracefully(): void
    {
        $admin = User::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'rc_expiry_date' => null,
            'insurance_expiry_date' => null,
        ]);

        $this->actingAs($admin)->get(route('admin.vehicles.show', $vehicle))
            ->assertStatus(200)
            ->assertSee('No expiry date recorded for this document.');
    }
}
