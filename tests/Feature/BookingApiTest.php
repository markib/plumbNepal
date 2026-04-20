<?php

namespace Tests\Feature;

use App\Models\BookingStatus;
use App\Models\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_booking_returns_201_and_persists_data()
    {
        BookingStatus::create([
            'id' => 1,
            'name' => 'Pending',
            'description' => 'Pending booking',
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Pipe Repair',
            'description' => 'Fix leak and replace joints',
            'fee' => 1200,
            'is_emergency_available' => true,
        ]);

        $payload = [
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'payment_method' => 'cod',
            'is_emergency' => false,
        ];

        $response = $this->postJson('/api/v1/bookings', $payload);

        $response->assertCreated()
            ->assertJsonPath('booking.service_type_id', $payload['service_type_id']);

        $this->assertDatabaseHas('bookings', [
            'service_type_id' => $serviceType->id,
            'payment_method' => 'cod',
        ]);
    }

    public function test_invalid_service_type_id_returns_422_with_specific_message()
    {
        BookingStatus::create([
            'id' => 1,
            'name' => 'Pending',
            'description' => 'Pending booking',
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => 9999,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.service_type_id.0', 'The selected service type id is invalid.');
    }

    public function test_missing_payment_method_returns_422_validation_error()
    {
        BookingStatus::create([
            'id' => 1,
            'name' => 'Pending',
            'description' => 'Pending booking',
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Pipe Repair',
            'description' => 'Fix leak and replace joints',
            'fee' => 1200,
            'is_emergency_available' => true,
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => $serviceType->id,
            'latitude' => 27.7172,
            'longitude' => 85.3240,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.payment_method.0', 'The payment method field is required.');
    }

    public function test_invalid_coordinates_return_422_validation_error()
    {
        BookingStatus::create([
            'id' => 1,
            'name' => 'Pending',
            'description' => 'Pending booking',
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Pipe Repair',
            'description' => 'Fix leak and replace joints',
            'fee' => 1200,
            'is_emergency_available' => true,
        ]);

        $response = $this->postJson('/api/v1/bookings', [
            'service_type_id' => $serviceType->id,
            'latitude' => 'invalid-latitude',
            'longitude' => 'invalid-longitude',
            'payment_method' => 'cod',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.latitude.0', 'The latitude must be a number.')
            ->assertJsonPath('errors.longitude.0', 'The longitude must be a number.');
    }
}
