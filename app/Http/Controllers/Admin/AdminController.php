<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Application;
use App\Models\Period;
use App\Models\Criteria;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_applications' => Application::count(),
            'active_periods' => Period::where('is_active', true)->count(),
            'total_criterias' => Criteria::where('is_active', true)->count(),
        ];

        $recentApplications = Application::with(['user', 'period'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentApplications'));
    }
}
