<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'criteria_type',
        'criteria_id',
        'value',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:8',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function getValueAttribute($value)
    {
        // Try to decode as JSON, return as string if not JSON
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
    }
}