<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\CvService;
use App\Support\Audit;
use Illuminate\Http\Request;

/** CV hanya dapat dibuat dan diunduh oleh HR (dibatasi middleware role:HR). */
class CvController extends Controller
{
    public function __construct(private CvService $cv) {}

    public function index(Request $request)
    {
        $employees = EmployeeController::filteredQuery($request->only(['q', 'status', 'project']))
            ->with('activeProjects')->orderBy('nama')->paginate(25)->withQueryString();

        return view('cv.index', ['employees' => $employees]);
    }

    public function show(Employee $employee)
    {
        $this->cv->load($employee);

        return view('cv.show', ['employee' => $employee, 'check' => $this->cv->checklist($employee)]);
    }

    public function preview(Employee $employee)
    {
        return response($this->cv->html($employee));
    }

    public function pdf(Employee $employee)
    {
        $check = $this->cv->checklist($this->cv->load($employee));
        if (! $check['ready']) {
            return redirect()->route('cv.show', $employee)->with('error', 'CV final belum dapat diunduh: lengkapi data wajib terlebih dahulu.');
        }

        $pdf = $this->cv->pdf($employee);
        Audit::log('employee', $employee->employee_id, 'GenerateCV');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->cv->fileName($employee).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
