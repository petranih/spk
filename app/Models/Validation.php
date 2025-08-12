<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'validator_id',
        'status',
        'notes',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }
}