<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'criteria_type', // 'criteria', 'subcriteria', 'subsubcriteria'
        'criteria_id',
        'value',
        'score'
    ];

    protected $casts = [
        'score' => 'decimal:10'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Relasi polymorphic ke kriteria yang sesuai
     */
    public function criteria()
    {
        switch ($this->criteria_type) {
            case 'criteria':
                return $this->belongsTo(Criteria::class, 'criteria_id');
            case 'subcriteria':
                return $this->belongsTo(SubCriteria::class, 'criteria_id');
            case 'subsubcriteria':
                return $this->belongsTo(SubSubCriteria::class, 'criteria_id');
            default:
                return null;
        }
    }

    /**
     * Scope untuk filter berdasarkan tipe kriteria
     */
    public function scopeForCriteriaType($query, $type)
    {
        return $query->where('criteria_type', $type);
    }

    /**
     * Scope untuk filter berdasarkan aplikasi
     */
    public function scopeForApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    /**
     * Get nilai yang sudah diformat
     */
    public function getFormattedValueAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}