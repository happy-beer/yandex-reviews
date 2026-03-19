<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardStatsService $statsService): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $statsService->forUser((int) $request->user()->id),
        ]);
    }
}
