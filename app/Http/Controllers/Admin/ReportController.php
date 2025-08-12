<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Period;
use App\Models\Application;
use App\Models\Ranking;
use App\Http\Controllers\AHPController;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $periods = Period::with(['applications', 'rankings'])
            ->latest()
            ->get();

        return view('admin.report.index', compact('periods'));
    }

    public function show(Period $period)
    {
        $rankings = Ranking::where('period_id', $period->id)
            ->with(['application.user'])
            ->orderBy('rank')
            ->paginate(20);

        $applications = Application::where('period_id', $period->id)
            ->with(['user', 'validation'])
            ->get();

        $stats = [
            'total_applications' => $applications->count(),
            'validated_applications' => $applications->where('status', 'validated')->count(),
            'rejected_applications' => $applications->where('status', 'rejected')->count(),
            'pending_applications' => $applications->where('status', 'submitted')->count(),
        ];

        return view('admin.report.show', compact('period', 'rankings', 'stats'));
    }

    public function calculate(Period $period)
    {
        $ahpController = new AHPController();
        $rankings = $ahpController->calculateAllApplicationsScores($period->id);

        return redirect()->route('admin.report.show', $period->id)
            ->with('success', 'Perhitungan AHP berhasil dijalankan. Total: ' . $rankings->count() . ' aplikasi dihitung.');
    }

    public function exportPdf(Period $period)
    {
        $rankings = Ranking::where('period_id', $period->id)
            ->with(['application.user'])
            ->orderBy('rank')
            ->get();

        $pdf = Pdf::loadView('admin.report.pdf', compact('period', 'rankings'));
        
        return $pdf->download('ranking-beasiswa-' . $period->name . '.pdf');
    }

    public function exportExcel(Period $period)
    {
        // Implementation for Excel export
        // You can use Laravel Excel package for this
        return redirect()->route('admin.report.show', $period->id)
            ->with('info', 'Fitur export Excel belum tersedia');
    }
}