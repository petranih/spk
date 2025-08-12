<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCriteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'criteria_id',
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

    public function criteria()
{
    return $this->belongsTo(Criteria::class, 'criteria_id');
}


    public function subSubCriterias()
    {
        return $this->hasMany(SubSubCriteria::class)->orderBy('order');
    }

    public function pairwiseComparisons()
    {
        return $this->hasMany(PairwiseComparison::class, 'item_a_id')
                   ->where('comparison_type', 'subcriteria')
                   ->where('parent_id', $this->criteria_id);
    }

    public function weights()
    {
        return $this->hasOne(CriteriaWeight::class, 'item_id')
                   ->where('level', 'subcriteria');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}