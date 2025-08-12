<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PairwiseComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'comparison_type',
        'parent_id',
        'item_a_id',
        'item_b_id',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:8',
    ];

    // Get the reciprocal comparison (B vs A)
    public function reciprocal()
    {
        return $this->where('comparison_type', $this->comparison_type)
                   ->where('parent_id', $this->parent_id)
                   ->where('item_a_id', $this->item_b_id)
                   ->where('item_b_id', $this->item_a_id)
                   ->first();
    }
}