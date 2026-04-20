<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'fee',
        'is_emergency_available',
    ];

    protected $casts = [
        'fee' => 'integer',
        'is_emergency_available' => 'boolean',
    ];
}
