<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $maxThreshold = max(config('karyawan.contract_thresholds'));

        // Kontrak yang berakhir ≤ 90 hari atau sudah lewat, selama karyawan masih aktif.
        $contracts = Employee::active()
            ->whereNotNull('akhir_kontrak')
            ->where('akhir_kontrak', '<=', $today->copy()->addDays($maxThreshold))
            ->with('activeProjects')
            ->orderBy('akhir_kontrak')
            ->get();

        $buckets = [
            'lewat' => $contracts->filter(fn ($e) => $e->contractDaysLeft() < 0)->count(),
            '30' => $contracts->filter(fn ($e) => $e->contractDaysLeft() >= 0 && $e->contractDaysLeft() <= 30)->count(),
            '60' => $contracts->filter(fn ($e) => $e->contractDaysLeft() > 30 && $e->contractDaysLeft() <= 60)->count(),
            '90' => $contracts->filter(fn ($e) => $e->contractDaysLeft() > 60 && $e->contractDaysLeft() <= 90)->count(),
        ];

        return view('dashboard', [
            'activeCount' => Employee::where('status_karyawan', 'Aktif')->count(),
            'inactiveCount' => Employee::where('status_karyawan', 'Nonaktif')->count(),
            'activeProjects' => Project::active()->count(),
            'contracts' => $contracts,
            'buckets' => $buckets,
        ]);
    }
}
