<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ranking extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'application_id',
        'total_score',
        'rank',
        'criteria_scores',
    ];

    protected $casts = [
        'total_score' => 'decimal:8',
        'criteria_scores' => 'array',
    ];

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}