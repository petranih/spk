<?php
// app/Http/Controllers/Student/StudentController.php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Period;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $activePeriod = Period::active()->first();
        
        $applications = Application::where('user_id', $user->id)
            ->with(['period', 'validation'])
            ->latest()
            ->get();

        $currentApplication = null;
        if ($activePeriod) {
            $currentApplication = Application::where('user_id', $user->id)
                ->where('period_id', $activePeriod->id)
                ->first();
        }

        return view('student.dashboard', compact('applications', 'activePeriod', 'currentApplication'));
    }
}