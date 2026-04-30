<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plumber_profile_id',
        'accepted_by_id',
        'service_type_id',
        'status_id',
        'workflow_status',
        'payment_method',
        'amount',
        'is_emergency',
        'landmark',
        'ward_number',
        'tole_name',
        'service_notes',
        'latitude',
        'longitude',
        'contract_terms',
        'contract_start_code',
        'contracted_at',
        'pickup_location',
        'ai_diagnosis_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_emergency' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'contract_terms' => 'array',
        'job_order_json' => 'array',
        'contracted_at' => 'datetime',
        'job_started_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plumber()
    {
        return $this->belongsTo(PlumberProfile::class, 'plumber_profile_id');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(PlumberProfile::class, 'accepted_by_id');
    }

    public function status()
    {
        return $this->belongsTo(BookingStatus::class);
    }

    public function proposals()
    {
        return $this->hasMany(BookingProposal::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function aiDiagnosis()
    {
        return $this->belongsTo(AiDiagnosis::class);
    }
}
