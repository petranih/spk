<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        'score' => 'decimal:4,2'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Relasi polymorphic ke kriteria yang sesuai
     * PERBAIKAN: Tambahkan error handling dan logging
     */
    public function criteria()
    {
        try {
            switch ($this->criteria_type) {
                case 'criteria':
                    return $this->belongsTo(Criteria::class, 'criteria_id');
                case 'subcriteria':
                    return $this->belongsTo(SubCriteria::class, 'criteria_id');
                case 'subsubcriteria':
                    return $this->belongsTo(SubSubCriteria::class, 'criteria_id');
                default:
                    Log::warning('Unknown criteria type in ApplicationValue', [
                        'id' => $this->id,
                        'criteria_type' => $this->criteria_type
                    ]);
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Error in ApplicationValue criteria relation', [
                'id' => $this->id,
                'criteria_type' => $this->criteria_type,
                'criteria_id' => $this->criteria_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the related criteria instance (improved)
     * PERBAIKAN: Method untuk mendapatkan instance kriteria yang benar
     */
    public function getCriteriaInstance()
    {
        try {
            switch ($this->criteria_type) {
                case 'criteria':
                    return Criteria::find($this->criteria_id);
                case 'subcriteria':
                    return SubCriteria::find($this->criteria_id);
                case 'subsubcriteria':
                    return SubSubCriteria::find($this->criteria_id);
                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Error getting criteria instance', [
                'application_value_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get the actual selected option for this criteria value
     * PERBAIKAN: Method untuk mendapatkan opsi yang dipilih
     */
    public function getSelectedOption()
    {
        try {
            // Jika value adalah ID dari sub-sub-criteria
            if ($this->criteria_type === 'subcriteria' && is_numeric($this->value)) {
                $subSubCriteria = SubSubCriteria::find($this->value);
                if ($subSubCriteria) {
                    return $subSubCriteria;
                }
            }
            
            // Jika value adalah ID dari sub-criteria
            if ($this->criteria_type === 'criteria' && is_numeric($this->value)) {
                $subCriteria = SubCriteria::find($this->value);
                if ($subCriteria) {
                    return $subCriteria;
                }
            }
            
            // Default: return the criteria instance itself
            return $this->getCriteriaInstance();
        } catch (\Exception $e) {
            Log::error('Error getting selected option', [
                'application_value_id' => $this->id,
                'error' => $e->getMessage()
            ]);
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

    /**
     * Get display name for this application value
     * PERBAIKAN: Method untuk mendapatkan nama yang bisa ditampilkan
     */
    public function getDisplayNameAttribute()
    {
        try {
            $criteria = $this->getCriteriaInstance();
            if (!$criteria) {
                return 'Unknown Criteria';
            }

            $selectedOption = $this->getSelectedOption();
            if ($selectedOption && $selectedOption->id !== $criteria->id) {
                return $criteria->name . ' - ' . $selectedOption->name;
            }

            return $criteria->name;
        } catch (\Exception $e) {
            Log::error('Error getting display name', [
                'application_value_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 'Error: Cannot determine name';
        }
    }

    /**
     * Validate the score based on criteria type and value
     * PERBAIKAN: Method untuk validasi score
     */
    public function validateScore()
    {
        try {
            $expectedScore = $this->calculateExpectedScore();
            
            if ($expectedScore !== null && abs($this->score - $expectedScore) > 0.01) {
                Log::warning('Score mismatch detected', [
                    'application_value_id' => $this->id,
                    'current_score' => $this->score,
                    'expected_score' => $expectedScore
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error validating score', [
                'application_value_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Calculate what the score should be based on criteria and value
     * PERBAIKAN: Method untuk menghitung score yang seharusnya
     */
    public function calculateExpectedScore()
    {
        try {
            if ($this->criteria_type === 'subcriteria' && is_numeric($this->value)) {
                // Value is SubSubCriteria ID
                $subSubCriteria = SubSubCriteria::find($this->value);
                return $subSubCriteria ? $subSubCriteria->score : null;
            }
            
            if ($this->criteria_type === 'subsubcriteria') {
                $subSubCriteria = SubSubCriteria::find($this->criteria_id);
                return $subSubCriteria ? $subSubCriteria->score : null;
            }
            
            if ($this->criteria_type === 'criteria' && is_numeric($this->value)) {
                $criteria = Criteria::find($this->criteria_id);
                return $criteria ? ($criteria->score ?? 1) * intval($this->value) : null;
            }
            
            // Default calculation
            $criteria = $this->getCriteriaInstance();
            if ($criteria && isset($criteria->score)) {
                return $criteria->score;
            }
            
            return 1; // Default score
        } catch (\Exception $e) {
            Log::error('Error calculating expected score', [
                'application_value_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Boot method to add model events
     * PERBAIKAN: Tambahkan event handling
     */
    protected static function boot()
    {
        parent::boot();
        
        // Log when creating new application values
        static::creating(function ($applicationValue) {
            Log::info('Creating new application value', [
                'application_id' => $applicationValue->application_id,
                'criteria_type' => $applicationValue->criteria_type,
                'criteria_id' => $applicationValue->criteria_id,
                'value' => $applicationValue->value,
                'score' => $applicationValue->score
            ]);
        });
        
        // Validate score when updating
        static::updating(function ($applicationValue) {
            Log::info('Updating application value', [
                'id' => $applicationValue->id,
                'changes' => $applicationValue->getDirty()
            ]);
        });
        
        // Log when deleting
        static::deleting(function ($applicationValue) {
            Log::info('Deleting application value', [
                'id' => $applicationValue->id,
                'application_id' => $applicationValue->application_id,
                'criteria_type' => $applicationValue->criteria_type
            ]);
        });
    }

    /**
     * Convert the model to array with additional debug info
     * PERBAIKAN: Tambahkan debug info saat debugging
     */
    public function toDebugArray()
    {
        $array = $this->toArray();
        
        try {
            $criteria = $this->getCriteriaInstance();
            $selectedOption = $this->getSelectedOption();
            
            $array['debug_info'] = [
                'criteria_name' => $criteria ? $criteria->name : 'Not Found',
                'selected_option_name' => $selectedOption ? $selectedOption->name : 'Not Found',
                'expected_score' => $this->calculateExpectedScore(),
                'score_valid' => $this->validateScore(),
                'display_name' => $this->display_name
            ];
        } catch (\Exception $e) {
            $array['debug_info'] = [
                'error' => $e->getMessage()
            ];
        }
        
        return $array;
    }
}