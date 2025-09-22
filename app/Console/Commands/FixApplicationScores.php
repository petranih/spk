<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApplicationValue;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use Illuminate\Support\Facades\DB;

class FixApplicationScores extends Command
{
    protected $signature = 'fix:application-scores';
    protected $description = 'Fix application scores based on criteria IDs';

    public function handle()
    {
        $this->info('Starting to fix application scores...');
        
        // Get all ApplicationValues with score = 0
        $values = ApplicationValue::where('score', 0)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        $this->info("Found {$values->count()} records with zero scores to fix");

        $fixedCount = 0;
        $errorCount = 0;

        foreach ($values as $value) {
            try {
                $newScore = $this->calculateCorrectScore($value->criteria_type, $value->criteria_id, $value->value);
                
                if ($newScore > 0) {
                    // Use query builder to avoid BigDecimal issues
                    DB::table('application_values')
                        ->where('id', $value->id)
                        ->update(['score' => (float) $newScore]);
                    
                    $this->line("✓ Fixed ID {$value->id}: {$value->criteria_type}_{$value->criteria_id} = {$newScore}");
                    $fixedCount++;
                } else {
                    $this->line("⚠ No score found for ID {$value->id}: {$value->criteria_type}_{$value->criteria_id}");
                }
                
            } catch (\Exception $e) {
                $this->error("✗ Error fixing ID {$value->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("\nSummary:");
        $this->info("- Records processed: {$values->count()}");
        $this->info("- Successfully fixed: {$fixedCount}");
        $this->info("- Errors: {$errorCount}");
        
        // Verify results
        $remainingZeroScores = ApplicationValue::where('score', 0)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->count();
            
        $this->info("- Remaining zero scores: {$remainingZeroScores}");
        
        return Command::SUCCESS;
    }

    private function calculateCorrectScore($criteriaType, $criteriaId, $value)
    {
        try {
            $score = 0.0;

            if ($criteriaType === 'subcriteria') {
                $subCriteria = SubCriteria::find($criteriaId);
                if ($subCriteria && $subCriteria->score !== null) {
                    $rawScore = $subCriteria->score;
                    if (is_object($rawScore) && method_exists($rawScore, 'toFloat')) {
                        $score = $rawScore->toFloat();
                    } elseif (is_numeric($rawScore)) {
                        $score = (float) $rawScore;
                    }
                }
            } elseif ($criteriaType === 'subsubcriteria') {
                $subSubCriteria = SubSubCriteria::find($criteriaId);
                if ($subSubCriteria && $subSubCriteria->score !== null) {
                    $rawScore = $subSubCriteria->score;
                    if (is_object($rawScore) && method_exists($rawScore, 'toFloat')) {
                        $score = $rawScore->toFloat();
                    } elseif (is_numeric($rawScore)) {
                        $score = (float) $rawScore;
                    }
                }
            }

            // Fallback: use numeric value if no score from criteria
            if ($score <= 0 && is_numeric($value)) {
                $score = (float) $value;
            }

            // Minimal score for filled values
            if ($score <= 0 && !empty($value)) {
                $score = 1.0;
            }

            return max(0.0, (float) $score);

        } catch (\Exception $e) {
            $this->error("Error calculating score for {$criteriaType}_{$criteriaId}: " . $e->getMessage());
            return !empty($value) ? 1.0 : 0.0;
        }
    }
}