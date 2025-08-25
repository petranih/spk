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
        'criteria_scores',
        'rank',
        'calculated_at'
    ];

    protected $casts = [
        'criteria_scores' => 'array',
        'calculated_at' => 'datetime',
        'total_score' => 'decimal:10'
    ];

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function student()
    {
        return $this->hasOneThrough(User::class, Application::class, 'id', 'id', 'application_id', 'student_id');
    }

    /**
     * Scope untuk ranking berdasarkan periode
     */
    public function scopeForPeriod($query, $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope untuk ranking terbaik
     */
    public function scopeTopRanking($query, $limit = 10)
    {
        return $query->orderBy('rank', 'asc')->limit($limit);
    }

    /**
     * Get formatted rank
     */
    public function getFormattedRankAttribute()
    {
        return $this->rank ? 'Ranking ' . $this->rank : 'Belum diranking';
    }

    /**
     * Get formatted score
     */
    public function getFormattedScoreAttribute()
    {
        return number_format($this->total_score, 6);
    }
}