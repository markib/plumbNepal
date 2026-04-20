<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingProposal;
use App\Models\PlumberProfile;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleServiceDataSeeder extends Seeder
{
    public function run(): void
    {
        $serviceType = ServiceType::firstOrCreate(
            ['name' => 'General Plumbing'],
            [
                'description' => 'Standard plumbing services for household repairs and installations.',
                'fee' => 500,
                'is_emergency_available' => true,
            ]
        );

        $drainCleaningType = ServiceType::firstOrCreate(
            ['name' => 'Drain Cleaning'],
            [
                'description' => 'Drain cleaning and unclogging service for sinks, showers, and drains.',
                'fee' => 700,
                'is_emergency_available' => true,
            ]
        );

        $customerOne = User::firstOrCreate(
            ['email' => 'customer@plumbnepal.test'],
            [
                'name' => 'Test Customer',
                'phone' => '9800000001',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        $customerTwo = User::firstOrCreate(
            ['email' => 'customer2@plumbnepal.test'],
            [
                'name' => 'Second Customer',
                'phone' => '9800000006',
                'password' => bcrypt('password123'),
                'role' => 'customer',
                'locale' => 'en',
            ]
        );

        $plumberOne = User::firstOrCreate(
            ['email' => 'plumber@plumbnepal.test'],
            [
                'name' => 'Verified Plumber',
                'phone' => '9800000004',
                'password' => bcrypt('plumber1234'),
                'role' => 'plumber',
                'locale' => 'en',
            ]
        );

        $plumberTwo = User::firstOrCreate(
            ['email' => 'plumber2@plumbnepal.test'],
            [
                'name' => 'Second Plumber',
                'phone' => '9800000007',
                'password' => bcrypt('plumber1234'),
                'role' => 'plumber',
                'locale' => 'en',
            ]
        );

        $profileOne = PlumberProfile::updateOrCreate(
            ['user_id' => $plumberOne->id],
            [
                'service_type_ids' => [$serviceType->id],
                'is_available' => true,
                'is_online' => true,
                'available_since' => now()->subHours(2),
                'verified' => true,
                'rating' => 4.9,
                'location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3240 27.7172)')"),
            ]
        );

        $profileTwo = PlumberProfile::updateOrCreate(
            ['user_id' => $plumberTwo->id],
            [
                'service_type_ids' => [$serviceType->id],
                'is_available' => true,
                'is_online' => true,
                'available_since' => now()->subHours(1),
                'verified' => true,
                'rating' => 4.7,
                'location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3335 27.7150)')"),
            ]
        );

        $bookingOne = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Thamel',
                'ward_number' => '01',
                'tole_name' => 'Old Bazaar',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'proposed',
                'payment_method' => 'cod',
                'amount' => 1200,
                'is_emergency' => false,
                'service_notes' => 'Kitchen sink leak and low water pressure.',
                'latitude' => 27.7172,
                'longitude' => 85.3240,
                'pickup_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3240 27.7172)')"),
            ]
        );

        BookingProposal::updateOrCreate(
            [
                'booking_id' => $bookingOne->id,
                'plumber_profile_id' => $profileOne->id,
            ],
            [
                'base_fee' => 850,
                'material_cost' => 150,
                'eta_minutes' => 45,
                'proposal_terms' => ['notes' => 'Can repair same day with spare parts included.'],
                'status' => 'proposed',
            ]
        );

        BookingProposal::updateOrCreate(
            [
                'booking_id' => $bookingOne->id,
                'plumber_profile_id' => $profileTwo->id,
            ],
            [
                'base_fee' => 800,
                'material_cost' => 180,
                'eta_minutes' => 40,
                'proposal_terms' => ['notes' => 'Available immediately and can inspect for hidden leaks.'],
                'status' => 'proposed',
            ]
        );

        $bookingTwo = Booking::updateOrCreate(
            [
                'user_id' => $customerTwo->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Boudha',
                'ward_number' => '05',
                'tole_name' => 'Boudha Marg',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'esewa',
                'amount' => 950,
                'is_emergency' => false,
                'service_notes' => 'Toilet flush not working properly.',
                'latitude' => 27.7211,
                'longitude' => 85.3604,
                'pickup_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3604 27.7211)')"),
            ]
        );

        $bookingOpenOne = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Kathmandu Guest House',
                'ward_number' => '01',
                'tole_name' => 'Thamel',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'cod',
                'amount' => 1100,
                'is_emergency' => false,
                'service_notes' => 'Hot water line leaking in the bathroom.',
                'latitude' => 27.7165,
                'longitude' => 85.3255,
                'pickup_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3255 27.7165)')"),
            ]
        );

        $bookingOpenTwo = Booking::updateOrCreate(
            [
                'user_id' => $customerTwo->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'Lazimpat',
                'ward_number' => '13',
                'tole_name' => 'Shangri-La Area',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'cod',
                'amount' => 980,
                'is_emergency' => false,
                'service_notes' => 'Low pressure in bathroom tap and noisy pump.',
                'latitude' => 27.7155,
                'longitude' => 85.3330,
                'pickup_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3330 27.7155)')"),
            ]
        );

        $bookingOpenThree = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $drainCleaningType->id,
                'landmark' => 'New Road',
                'ward_number' => '11',
                'tole_name' => 'New Road Street',
            ],
            [
                'status_id' => 1,
                'workflow_status' => 'pending',
                'payment_method' => 'cod',
                'amount' => 880,
                'is_emergency' => false,
                'service_notes' => 'Drain in kitchen sink keeps clogging when washing dishes.',
                'latitude' => 27.7138,
                'longitude' => 85.3203,
                'pickup_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3203 27.7138)')"),
            ]
        );

        $bookingThree = Booking::updateOrCreate(
            [
                'user_id' => $customerOne->id,
                'service_type_id' => $serviceType->id,
                'landmark' => 'New Baneshwor',
                'ward_number' => '09',
                'tole_name' => 'Bakhundol',
            ],
            [
                'status_id' => 2,
                'workflow_status' => 'contracted',
                'payment_method' => 'khalti',
                'amount' => 1500,
                'is_emergency' => true,
                'service_notes' => 'Burst pipe repair in bathroom.',
                'latitude' => 27.7114,
                'longitude' => 85.3296,
                'pickup_location' => DB::raw("ST_GeogFromText('SRID=4326;POINT(85.3296 27.7114)')"),
                'plumber_profile_id' => $profileOne->id,
                'accepted_by_id' => $profileOne->id,
                'contract_terms' => [
                    'base_fee' => 1200,
                    'material_cost' => 300,
                    'eta_minutes' => 60,
                    'details' => ['repair' => 'pipe replacement', 'warranty' => '7 days'],
                ],
                'contract_start_code' => '7421',
                'contracted_at' => now()->subHours(3),
                'job_order_json' => [
                    'booking_id' => null,
                    'customer_id' => $customerOne->id,
                    'plumber_profile_id' => $profileOne->id,
                    'contract_terms' => [
                        'base_fee' => 1200,
                        'material_cost' => 300,
                        'eta_minutes' => 60,
                    ],
                    'created_at' => now()->toIso8601String(),
                ],
            ]
        );

        if (is_array($bookingThree->job_order_json)) {
            $jobOrderJson = $bookingThree->job_order_json;
            $jobOrderJson['booking_id'] = $bookingThree->id;
            $bookingThree->job_order_json = $jobOrderJson;
            $bookingThree->save();
        }
    }
}
