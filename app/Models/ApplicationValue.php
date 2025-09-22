<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'criteria_type',
        'criteria_id',
        'value',
        'score',
    ];

    // FIXED: Cast score ke float untuk menghindari masalah BigDecimal
    protected $casts = [
        'application_id' => 'integer',
        'criteria_id' => 'integer',
        'score' => 'float', // Ubah dari decimal ke float
    ];

    // FIXED: Accessor untuk memastikan score selalu return float
    public function getScoreAttribute($value)
    {
        if ($value === null) {
            return 0.0;
        }
        
        // Handle BigDecimal objects
        if (is_object($value) && method_exists($value, 'toFloat')) {
            return $value->toFloat();
        }
        
        // Convert to float
        return (float) $value;
    }

    // FIXED: Mutator untuk memastikan score disimpan sebagai float
    public function setScoreAttribute($value)
    {
        if ($value === null) {
            $this->attributes['score'] = 0.0;
            return;
        }
        
        // Handle BigDecimal objects
        if (is_object($value) && method_exists($value, 'toFloat')) {
            $this->attributes['score'] = $value->toFloat();
            return;
        }
        
        // Convert to float
        $this->attributes['score'] = (float) $value;
    }

    // Relationships
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class, 'criteria_id')
            ->where('criteria_type', 'criteria');
    }

    public function subCriteria()
    {
        return $this->belongsTo(SubCriteria::class, 'criteria_id')
            ->where('criteria_type', 'subcriteria');
    }

    public function subSubCriteria()
    {
        return $this->belongsTo(SubSubCriteria::class, 'criteria_id')
            ->where('criteria_type', 'subsubcriteria');
    }

    // Scopes
    public function scopeForApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeByType($query, $criteriaType)
    {
        return $query->where('criteria_type', $criteriaType);
    }

    public function scopeByCriteriaId($query, $criteriaId)
    {
        return $query->where('criteria_id', $criteriaId);
    }

    // Helper methods
    public function getCriteriaName()
    {
        switch ($this->criteria_type) {
            case 'criteria':
                return $this->criteria ? $this->criteria->name : 'Unknown Criteria';
            case 'subcriteria':
                return $this->subCriteria ? $this->subCriteria->name : 'Unknown SubCriteria';
            case 'subsubcriteria':
                return $this->subSubCriteria ? $this->subSubCriteria->name : 'Unknown SubSubCriteria';
            default:
                return 'Unknown';
        }
    }

    public function getCriteriaCode()
    {
        switch ($this->criteria_type) {
            case 'criteria':
                return $this->criteria ? $this->criteria->code : 'UNK';
            case 'subcriteria':
                return $this->subCriteria ? $this->subCriteria->code : 'UNK';
            case 'subsubcriteria':
                return $this->subSubCriteria ? $this->subSubCriteria->code : 'UNK';
            default:
                return 'UNK';
        }
    }

    // FIXED: Method untuk mendapatkan score sebagai float dengan safety check
    public function getFloatScore()
    {
        $score = $this->score;
        
        if ($score === null) {
            return 0.0;
        }
        
        if (is_object($score) && method_exists($score, 'toFloat')) {
            return $score->toFloat();
        }
        
        if (is_numeric($score)) {
            return (float) $score;
        }
        
        return 0.0;
    }

    // Method untuk debugging score
    public function debugScore()
    {
        return [
            'raw_score' => $this->attributes['score'] ?? null,
            'raw_score_type' => gettype($this->attributes['score'] ?? null),
            'processed_score' => $this->score,
            'processed_score_type' => gettype($this->score),
            'float_score' => $this->getFloatScore(),
            'is_object' => is_object($this->attributes['score'] ?? null),
            'has_toFloat' => is_object($this->attributes['score'] ?? null) && method_exists($this->attributes['score'], 'toFloat'),
        ];
    }
}