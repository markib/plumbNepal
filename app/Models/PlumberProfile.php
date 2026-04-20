<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlumberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_type_ids',
        'location',
        'is_available',
        'is_online',
        'available_since',
        'availability_notes',
        'verified',
        'rating',
    ];

    protected $casts = [
        'service_type_ids' => 'array',
        'is_available' => 'boolean',
        'is_online' => 'boolean',
        'verified' => 'boolean',
        'rating' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'plumber_profile_id');
    }
}
