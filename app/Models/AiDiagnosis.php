<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDiagnosis extends Model
{
    protected $fillable = [
        'issue_type', 'urgency', 'price_min', 'price_max', 'service', 'confidence', 'summary', 'raw', 'model', 'prompt_version'
    ];

    protected $casts = [
        'raw_response' => 'array',
        'confidence' => 'float',
    ];
}
