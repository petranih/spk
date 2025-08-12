<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Validation;
use Illuminate\Support\Facades\Auth;

class ValidatorController extends Controller
{
    public function dashboard()
    {
        $pendingApplications = Application::where('status', 'submitted')
            ->with(['user', 'period'])
            ->latest()
            ->take(10)
            ->get();

        $myValidations = Validation::where('validator_id', Auth::id())
            ->with(['application.user', 'application.period'])
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'pending' => Application::where('status', 'submitted')->count(),
            'validated' => Validation::where('validator_id', Auth::id())->count(),
            'approved' => Validation::where('validator_id', Auth::id())->where('status', 'approved')->count(),
            'rejected' => Validation::where('validator_id', Auth::id())->where('status', 'rejected')->count(),
        ];

        return view('validator.dashboard', compact('pendingApplications', 'myValidations', 'stats'));
    }
}