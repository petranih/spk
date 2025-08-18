<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'is_consistent'
    ];

    protected $casts = [
        'weight' => 'decimal:6',
        'lambda_max' => 'decimal:6',
        'ci' => 'decimal:6',
        'cr' => 'decimal:6',
        'is_consistent' => 'boolean'
    ];

    /**
     * Get the criteria associated with this weight (for criteria level)
     */
    public function criteria()
    {
        return $this->belongsTo(Criteria::class, 'item_id')->when($this->level === 'criteria');
    }

    /**
     * Get the sub criteria associated with this weight (for subcriteria level)
     */
    public function subCriteria()
    {
        return $this->belongsTo(SubCriteria::class, 'item_id')->when($this->level === 'subcriteria');
    }

    /**
     * Get the sub-sub criteria associated with this weight (for subsubcriteria level)
     */
    public function subSubCriteria()
    {
        return $this->belongsTo(SubSubCriteria::class, 'item_id')->when($this->level === 'subsubcriteria');
    }

    /**
     * Get parent criteria (for subcriteria level)
     */
    public function parentCriteria()
    {
        return $this->belongsTo(Criteria::class, 'parent_id')->when($this->level === 'subcriteria');
    }

    /**
     * Get parent sub criteria (for subsubcriteria level)
     */
    public function parentSubCriteria()
    {
        return $this->belongsTo(SubCriteria::class, 'parent_id')->when($this->level === 'subsubcriteria');
    }

    /**
     * Scope for consistent weights
     */
    public function scopeConsistent($query)
    {
        return $query->where('is_consistent', true);
    }

    /**
     * Scope for inconsistent weights
     */
    public function scopeInconsistent($query)
    {
        return $query->where('is_consistent', false);
    }

    /**
     * Scope for specific level
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Get consistency status text
     */
    public function getConsistencyStatusAttribute()
    {
        return $this->is_consistent ? 'Konsisten' : 'Tidak Konsisten';
    }

    /**
     * Get consistency color for UI
     */
    public function getConsistencyColorAttribute()
    {
        return $this->is_consistent ? 'success' : 'danger';
    }

    /**
     * Get CR percentage for progress bar
     */
    public function getCrPercentageAttribute()
    {
        return min($this->cr * 100, 100);
    }

    /**
     * Get the name of the associated item
     */
    public function getItemNameAttribute()
    {
        switch ($this->level) {
            case 'criteria':
                return $this->criteria?->name ?? 'Unknown Criteria';
            case 'subcriteria':
                return $this->subCriteria?->name ?? 'Unknown Sub Criteria';
            case 'subsubcriteria':
                return $this->subSubCriteria?->name ?? 'Unknown Sub-Sub Criteria';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the code of the associated item
     */
    public function getItemCodeAttribute()
    {
        switch ($this->level) {
            case 'criteria':
                return $this->criteria?->code ?? 'Unknown';
            case 'subcriteria':
                return $this->subCriteria?->code ?? 'Unknown';
            case 'subsubcriteria':
                return $this->subSubCriteria?->code ?? 'Unknown';
            default:
                return 'Unknown';
        }
    }

    /**
     * Calculate global weight for hierarchical structure
     */
    public function getGlobalWeightAttribute()
    {
        switch ($this->level) {
            case 'criteria':
                return $this->weight;
                
            case 'subcriteria':
                $parentWeight = $this->parentCriteria?->weight ?? 1;
                return $this->weight * $parentWeight;
                
            case 'subsubcriteria':
                $parentSubCriteria = $this->parentSubCriteria;
                if (!$parentSubCriteria) return $this->weight;
                
                $parentCriteria = $parentSubCriteria->criteria;
                if (!$parentCriteria) return $this->weight * $parentSubCriteria->weight;
                
                return $this->weight * $parentSubCriteria->weight * $parentCriteria->weight;
                
            default:
                return $this->weight;
        }
    }

    /**
     * Get hierarchy path for display
     */
    public function getHierarchyPathAttribute()
    {
        switch ($this->level) {
            case 'criteria':
                return $this->criteria?->code ?? 'Unknown';
                
            case 'subcriteria':
                $parent = $this->parentCriteria?->code ?? 'Unknown';
                $current = $this->subCriteria?->code ?? 'Unknown';
                return "{$parent} → {$current}";
                
            case 'subsubcriteria':
                $parentSub = $this->parentSubCriteria;
                if (!$parentSub) return $this->subSubCriteria?->code ?? 'Unknown';
                
                $grandParent = $parentSub->criteria?->code ?? 'Unknown';
                $parent = $parentSub->code;
                $current = $this->subSubCriteria?->code ?? 'Unknown';
                return "{$grandParent} → {$parent} → {$current}";
                
            default:
                return 'Unknown';
        }
    }

    /**
     * Static method to get consistency summary
     */
    public static function getConsistencySummary()
    {
        $summary = [
            'criteria' => [
                'total' => self::where('level', 'criteria')->count(),
                'consistent' => self::where('level', 'criteria')->where('is_consistent', true)->count(),
                'average_cr' => self::where('level', 'criteria')->avg('cr') ?? 0,
            ],
            'subcriteria' => [
                'total' => self::where('level', 'subcriteria')->count(),
                'consistent' => self::where('level', 'subcriteria')->where('is_consistent', true)->count(),
                'average_cr' => self::where('level', 'subcriteria')->avg('cr') ?? 0,
            ],
            'subsubcriteria' => [
                'total' => self::where('level', 'subsubcriteria')->count(),
                'consistent' => self::where('level', 'subsubcriteria')->where('is_consistent', true)->count(),
                'average_cr' => self::where('level', 'subsubcriteria')->avg('cr') ?? 0,
            ],
        ];

        $totalMatrices = $summary['criteria']['total'] + $summary['subcriteria']['total'] + $summary['subsubcriteria']['total'];
        $totalConsistent = $summary['criteria']['consistent'] + $summary['subcriteria']['consistent'] + $summary['subsubcriteria']['consistent'];

        $summary['overall'] = [
            'total' => $totalMatrices,
            'consistent' => $totalConsistent,
            'percentage' => $totalMatrices > 0 ? round(($totalConsistent / $totalMatrices) * 100, 1) : 0,
            'average_cr' => self::avg('cr') ?? 0,
        ];

        return $summary;
    }

    /**
     * Check if all matrices at a level are consistent
     */
    public static function isLevelConsistent($level)
    {
        $total = self::where('level', $level)->count();
        if ($total == 0) return true; // No matrices means consistent by default
        
        $consistent = self::where('level', $level)->where('is_consistent', true)->count();
        return $total === $consistent;
    }

    /**
     * Get worst CR values for a level (most inconsistent)
     */
    public static function getWorstCRForLevel($level, $limit = 5)
    {
        return self::where('level', $level)
            ->where('is_consistent', false)
            ->orderBy('cr', 'desc')
            ->limit($limit)
            ->get();
    }
}