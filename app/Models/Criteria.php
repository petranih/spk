<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Criteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'order',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:8',
        'is_active' => 'boolean',
    ];

public function subCriterias()
{
    return $this->hasMany(SubCriteria::class, 'criteria_id');
}


    public function pairwiseComparisons()
    {
        return $this->hasMany(PairwiseComparison::class, 'item_a_id')
                   ->where('comparison_type', 'criteria');
    }

    public function weights()
    {
        return $this->hasOne(CriteriaWeight::class, 'item_id')
                   ->where('level', 'criteria');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
