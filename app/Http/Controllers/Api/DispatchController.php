<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PlumberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function findNearbyPlumbers(float $latitude, float $longitude, ?int $serviceTypeId = null, int $radius = 5000)
    {
        $point = sprintf('SRID=4326;POINT(%s %s)', $longitude, $latitude);

        return PlumberProfile::with('user')->selectRaw(
            'plumber_profiles.*, ST_Distance(location, ST_GeogFromText(?)) AS distance_meters',
            [$point]
        )
        ->where('is_available', true)
        ->where('verified', true)
        ->when($serviceTypeId, function ($query, $serviceTypeId) {
            $query->whereJsonContains('service_type_ids', $serviceTypeId);
        })
        ->whereRaw('ST_DWithin(location, ST_GeogFromText(?), ?)', [$point, $radius])
        ->orderBy('distance_meters', 'asc')
        ->limit(20)
        ->get();
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meters' => 'nullable|integer|min:500',
            'service_type_id' => 'nullable|integer|exists:service_types,id',
        ]);

        $radius = $data['radius_meters'] ?? 5000;
        $plumbers = $this->findNearbyPlumbers(
            $data['latitude'],
            $data['longitude'],
            $data['service_type_id'] ?? null,
            $radius
        );

        return response()->json([ 'data' => $plumbers ]);
    }

    public function searchBooking(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'radius_meters' => 'nullable|integer|min:500',
        ]);

        if (! isset($booking->latitude) || ! isset($booking->longitude)) {
            return response()->json([ 'message' => 'Booking does not contain location data' ], 422);
        }

        $radius = $data['radius_meters'] ?? 5000;
        $plumbers = $this->findNearbyPlumbers(
            $booking->latitude,
            $booking->longitude,
            $booking->service_type_id,
            $radius
        );

        return response()->json([ 'data' => $plumbers ]);
    }

    public function updateAvailability(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json([ 'message' => 'Unauthorized' ], 403);
        }

        $data = $request->validate([
            'is_available' => 'required|boolean',
            'latitude' => 'required_with:is_available|numeric',
            'longitude' => 'required_with:is_available|numeric',
            'availability_notes' => 'nullable|string|max:255',
        ]);

        $profile = $user->plumberProfile;
        if (! $profile) {
            return response()->json([ 'message' => 'Plumber profile not found' ], 404);
        }

        $profile->is_available = $data['is_available'];
        $profile->availability_notes = $data['availability_notes'] ?? $profile->availability_notes;

        if (isset($data['latitude']) && isset($data['longitude'])) {
            $profile->location = DB::raw("ST_GeogFromText('SRID=4326;POINT({$data['longitude']} {$data['latitude']})')");
        }

        $profile->save();

        return response()->json([ 'message' => 'Availability updated', 'profile' => $profile ]);
    }
}
