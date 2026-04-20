<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingProposal;
use App\Models\PlumberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingProposalController extends Controller
{
    public function openRequests(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || ! $profile->is_available || ! $profile->is_online || ! $profile->verified) {
            return response()->json(['requests' => []]);
        }

        $coordinates = DB::table('plumber_profiles')
            ->where('id', $profile->id)
            ->selectRaw('ST_X(location::geometry) as lng, ST_Y(location::geometry) as lat')
            ->first();

        if (! $coordinates || $coordinates->lng === null || $coordinates->lat === null) {
            return response()->json(['requests' => []]);
        }

        $point = sprintf('SRID=4326;POINT(%s %s)', $coordinates->lng, $coordinates->lat);
        $radius = 10000;

        $requests = DB::table('bookings')
            ->join('service_types', 'service_types.id', '=', 'bookings.service_type_id')
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->where('bookings.workflow_status', 'pending')
            ->whereNull('bookings.accepted_by_id')
            ->whereRaw('ST_DWithin(bookings.pickup_location, ST_GeogFromText(?), ?)', [$point, $radius])
            ->select(
                'bookings.id',
                'bookings.landmark',
                'bookings.ward_number',
                'bookings.tole_name',
                'bookings.created_at',
                'bookings.latitude',
                'bookings.longitude',
                'service_types.name as service_type_name',
                'users.name as customer_name'
            )
            ->orderByRaw('ST_DistanceSphere(bookings.pickup_location, ST_GeogFromText(?))', [$point])
            ->limit(20)
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function store(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || ! $profile->is_online || ! $profile->is_available || ! $profile->verified) {
            return response()->json(['message' => 'Plumber not eligible to send proposals'], 403);
        }

        $request->validate([
            'base_fee' => 'required|integer|min:0',
            'material_cost' => 'required|integer|min:0',
            'eta_minutes' => 'required|integer|min:5',
            'proposal_terms' => 'nullable|array',
        ]);

        if ($booking->workflow_status !== 'pending' && $booking->workflow_status !== 'proposed') {
            return response()->json(['message' => 'Booking is no longer open for proposals'], 422);
        }

        $proposal = BookingProposal::create([
            'booking_id' => $booking->id,
            'plumber_profile_id' => $profile->id,
            'base_fee' => $request->input('base_fee'),
            'material_cost' => $request->input('material_cost'),
            'eta_minutes' => $request->input('eta_minutes'),
            'proposal_terms' => $request->input('proposal_terms'),
            'status' => 'proposed',
        ]);

        $booking->workflow_status = 'proposed';
        $booking->save();

        return response()->json(['proposal' => $proposal], 201);
    }

    public function customerProposals(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $proposals = BookingProposal::with(['booking', 'plumber.user'])
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('workflow_status', 'proposed');
            })
            ->where('status', 'proposed')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['proposals' => $proposals]);
    }

    public function customerJobOrders(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobOrders = Booking::with(['serviceType', 'acceptedBy.user'])
            ->where('user_id', $user->id)
            ->whereIn('workflow_status', ['contracted', 'in_progress', 'completed'])
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'service_type_name' => $booking->serviceType?->name,
                    'workflow_status' => $booking->workflow_status,
                    'contract_terms' => $booking->contract_terms,
                    'job_order' => $booking->job_order_json,
                    'contract_start_code' => $booking->contract_start_code,
                    'plumber' => [
                        'name' => $booking->acceptedBy?->user?->name,
                        'phone' => $booking->acceptedBy?->user?->phone,
                    ],
                    'location' => [
                        'landmark' => $booking->landmark,
                        'ward_number' => $booking->ward_number,
                        'tole_name' => $booking->tole_name,
                    ],
                    'contracted_at' => $booking->contracted_at?->toIso8601String(),
                    'job_started_at' => $booking->job_started_at?->toIso8601String(),
                ];
            });

        return response()->json(['job_orders' => $jobOrders]);
    }

    public function accept(Request $request, Booking $booking, BookingProposal $proposal)
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->user_id !== $user->id) {
            return response()->json(['message' => 'Not your booking'], 403);
        }

        if ($proposal->booking_id !== $booking->id || $proposal->status !== 'proposed') {
            return response()->json(['message' => 'Proposal cannot be accepted'], 422);
        }

        DB::transaction(function () use ($booking, $proposal) {
            BookingProposal::where('booking_id', $booking->id)
                ->where('id', '!=', $proposal->id)
                ->update(['status' => 'expired']);

            $proposal->status = 'accepted';
            $proposal->save();

            $booking->accepted_by_id = $proposal->plumber_profile_id;
            $booking->workflow_status = 'contracted';
            $booking->contract_terms = [
                'base_fee' => $proposal->base_fee,
                'material_cost' => $proposal->material_cost,
                'eta_minutes' => $proposal->eta_minutes,
                'details' => $proposal->proposal_terms,
            ];
            $booking->job_order_json = [
                'booking_id' => $booking->id,
                'customer_id' => $booking->user_id,
                'plumber_profile_id' => $proposal->plumber_profile_id,
                'contract_terms' => $booking->contract_terms,
                'created_at' => now()->toIso8601String(),
            ];
            $booking->contract_start_code = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $booking->contracted_at = now();
            $booking->save();
        });

        return response()->json([
            'message' => 'Deal accepted',
            'job_order' => [
                'booking_id' => $booking->id,
                'contract_terms' => $booking->contract_terms,
                'contract_start_code' => $booking->contract_start_code,
                'assigned_plumber_id' => $booking->accepted_by_id,
            ],
        ]);
    }

    public function assignedJobs(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile) {
            return response()->json(['jobs' => []]);
        }

        $jobs = Booking::with(['user', 'serviceType'])
            ->where('accepted_by_id', $profile->id)
            ->whereIn('workflow_status', ['contracted', 'in_progress'])
            ->get();

        return response()->json(['jobs' => $jobs]);
    }

    public function startJob(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || $booking->accepted_by_id !== $profile->id) {
            return response()->json(['message' => 'Not assigned to this booking'], 403);
        }

        if ($booking->workflow_status !== 'contracted') {
            return response()->json(['message' => 'Booking is not in a startable state'], 422);
        }

        $request->validate([
            'contract_start_code' => 'required|string|size:4',
        ]);

        if ($booking->contract_start_code !== $request->input('contract_start_code')) {
            return response()->json(['message' => 'Invalid start code'], 422);
        }

        $booking->workflow_status = 'in_progress';
        $booking->job_started_at = now();
        $booking->save();

        return response()->json([
            'message' => 'Job started',
            'job_started_at' => $booking->job_started_at,
            'job_order' => $booking->job_order_json ?? [
                'contract_terms' => $booking->contract_terms,
            ],
        ]);
    }

    public function completeJob(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($user->role !== 'plumber') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = PlumberProfile::where('user_id', $user->id)->first();
        if (! $profile || $booking->accepted_by_id !== $profile->id) {
            return response()->json(['message' => 'Not assigned to this booking'], 403);
        }

        if ($booking->workflow_status !== 'in_progress') {
            return response()->json(['message' => 'Booking is not currently in progress'], 422);
        }

        $booking->workflow_status = 'completed';
        $booking->save();

        return response()->json([
            'message' => 'Job completed',
            'booking' => $booking,
        ]);
    }
}
