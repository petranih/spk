<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubCriteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_criteria_id',
        'name',
        'code',
        'description',
        'order',
        'weight',
        'score',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:8',
        'score' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function subCriteria()
    {
        return $this->belongsTo(SubCriteria::class);
    }

    public function criteria()
    {
        return $this->hasOneThrough(Criteria::class, SubCriteria::class, 
            'id', 'id', 'sub_criteria_id', 'criteria_id');
    }

    public function pairwiseComparisons()
    {
        return $this->hasMany(PairwiseComparison::class, 'item_a_id')
                   ->where('comparison_type', 'subsubcriteria')
                   ->where('parent_id', $this->sub_criteria_id);
    }

    public function weights()
    {
        return $this->hasOne(CriteriaWeight::class, 'item_id')
                   ->where('level', 'subsubcriteria');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}