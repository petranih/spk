<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Models\ApplicationValue;
use App\Models\SubSubCriteria;

class RepairApplicationValues extends Command
{
    protected $signature = 'repair:application-values {periodId?}';
    protected $description = 'Repair application values dengan scores yang benar';

    public function handle()
    {
        $periodId = $this->argument('periodId');

        if ($periodId) {
            $applications = Application::where('period_id', $periodId)->get();
            $this->info("Repairing {$applications->count()} apps in period {$periodId}...");
        } else {
            $applications = Application::get();
            $this->info("Repairing all {$applications->count()} applications...");
        }

        $repaired = 0;

        foreach ($applications as $app) {
            $values = ApplicationValue::where('application_id', $app->id)->get();

            foreach ($values as $value) {
                if ($value->criteria_type === 'subsubcriteria') {
                    $ssc = SubSubCriteria::find($value->criteria_id);
                    if ($ssc && $ssc->weight) {
                        $value->update(['score' => $ssc->weight]);
                        $repaired++;
                    }
                }
            }
        }

        $this->info("✓ Repaired {$repaired} values");
    }
}