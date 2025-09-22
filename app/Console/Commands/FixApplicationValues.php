<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApplicationValue;
use App\Models\SubCriteria;
use App\Models\SubSubCriteria;
use Illuminate\Support\Facades\DB;

class FixApplicationValues extends Command
{
    protected $signature = 'fix:application-values {--dry-run : Show what would be changed without actually changing it}';
    protected $description = 'Fix existing application values to store names instead of IDs';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->info('🔧 Starting to fix existing application values...');
        }
        
        // Get all application values where value looks like an ID (numeric)
        $values = ApplicationValue::whereRaw('value REGEXP \'^[0-9]+$\'')->get();
        
        $this->info("📊 Found {$values->count()} records with numeric values to analyze");
        
        if ($values->count() === 0) {
            $this->info('✅ No numeric values found to fix!');
            return Command::SUCCESS;
        }
        
        $fixed = 0;
        $errors = 0;
        $skipped = 0;
        
        $this->withProgressBar($values, function ($value) use (&$fixed, &$errors, &$skipped, $isDryRun) {
            try {
                $newName = null;
                $currentValue = $value->value;
                
                if ($value->criteria_type === 'subcriteria') {
                    $criteria = SubCriteria::find((int) $currentValue);
                    if ($criteria) {
                        $newName = $criteria->name;
                    }
                } elseif ($value->criteria_type === 'subsubcriteria') {
                    $criteria = SubSubCriteria::find((int) $currentValue);
                    if ($criteria) {
                        $newName = $criteria->name;
                    }
                }
                
                if ($newName && $newName !== $currentValue) {
                    if ($isDryRun) {
                        $this->line("\n🔄 Would fix: App {$value->application_id} - {$value->criteria_type}_{$value->criteria_id}");
                        $this->line("   From: '{$currentValue}' → To: '{$newName}'");
                    } else {
                        DB::transaction(function () use ($value, $newName) {
                            $value->update(['value' => $newName]);
                        });
                    }
                    $fixed++;
                } else if (!$newName) {
                    $skipped++;
                    if ($isDryRun) {
                        $this->line("\n⚠️  Could not find criteria for: {$value->criteria_type} ID {$currentValue}");
                    }
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->line("\n❌ Error processing value ID {$value->id}: " . $e->getMessage());
            }
        });
        
        $this->newLine(2);
        
        // Summary
        $this->info('📈 Summary:');
        $this->table(['Status', 'Count'], [
            ['Total processed', $values->count()],
            [$isDryRun ? 'Would be fixed' : 'Fixed', $fixed],
            ['Skipped (no criteria found)', $skipped],
            ['Errors', $errors],
        ]);
        
        if ($isDryRun && $fixed > 0) {
            $this->info("\n▶️  To apply these changes, run: php artisan fix:application-values");
        } elseif (!$isDryRun && $fixed > 0) {
            $this->info('✅ Fix completed successfully!');
            
            // Verify results
            $remaining = ApplicationValue::whereRaw('value REGEXP \'^[0-9]+$\'')->count();
            $this->info("📊 Remaining numeric values: {$remaining}");
        }
        
        return Command::SUCCESS;
    }
}