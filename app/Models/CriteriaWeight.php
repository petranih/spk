<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CriteriaWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'item_id',
        'parent_id',
        'weight',
        'lambda_max',
        'ci',
        'cr',
        'is_consistent',
    ];

    protected $casts = [
        'weight' => 'decimal:8',
        'lambda_max' => 'decimal:8',
        'ci' => 'decimal:8',
        'cr' => 'decimal:8',
        'is_consistent' => 'boolean',
    ];

    public function criteria()
    {
        return $this->belongsTo(Criteria::class, 'item_id')
                   ->where('level', 'criteria');
    }

    public function subCriteria()
    {
        return $this->belongsTo(SubCriteria::class, 'item_id')
                   ->where('level', 'subcriteria');
    }

    public function subSubCriteria()
    {
        return $this->belongsTo(SubSubCriteria::class, 'item_id')
                   ->where('level', 'subsubcriteria');
    }
}