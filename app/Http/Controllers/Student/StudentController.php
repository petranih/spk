<?php
// app/Http/Controllers/Student/StudentController.php - PERBAIKAN

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Period;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Ambil periode aktif
        $activePeriod = Period::active()->first();
        
        // Ambil semua aplikasi user
        $applications = Application::where('user_id', $user->id)
            ->with(['period', 'validation'])
            ->latest()
            ->get();

        // Cari aplikasi untuk periode aktif (jika ada)
        $currentApplication = null;
        if ($activePeriod) {
            $currentApplication = Application::where('user_id', $user->id)
                ->where('period_id', $activePeriod->id)
                ->first();
        }

        // Ambil periode yang tersedia dan akan datang
        $availablePeriods = Period::available()->get();
        $upcomingPeriods = Period::upcoming()->get();

        return view('student.dashboard', compact(
            'applications', 
            'activePeriod', 
            'currentApplication',
            'availablePeriods',
            'upcomingPeriods'
        ));
    }
}