<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->employee_id) {
            return redirect()->route('dashboard');
        }

        // Hanya data milik akun yang login; tidak menerima ID karyawan dari URL.
        $employee = $user->employee()->with('activeProjects')->firstOrFail();
        $experiences = $employee->experiences()->latestFirst()->with('updater.employee')->get();

        return view('portal.index', [
            'employee' => $employee,
            'experiences' => $experiences,
            'lastUpdate' => $experiences->max('updated_at'),
        ]);
    }
}
