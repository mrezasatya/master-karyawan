<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $this->validated($request, 'asg-new', $employee);
        $employee->assignments()->create($data);

        return $this->back($employee, 'Penugasan proyek ditambahkan.');
    }

    public function update(Request $request, EmployeeProject $assignment)
    {
        $data = $this->validated($request, 'asg-'.$assignment->id, $assignment->employee, $assignment);
        $assignment->update($data);

        return $this->back($assignment->employee, 'Penugasan proyek diperbarui.');
    }

    public function destroy(EmployeeProject $assignment)
    {
        $employee = $assignment->employee;
        $assignment->delete();

        return $this->back($employee, 'Penugasan proyek dihapus.');
    }

    private function validated(Request $request, string $bag, Employee $employee, ?EmployeeProject $current = null): array
    {
        $data = $request->validateWithBag($bag, [
            'project_id' => ['required', Rule::exists('projects', 'id')],
            'assignment_start' => ['nullable', 'date'],
            'assignment_end' => ['nullable', 'date', 'after_or_equal:assignment_start'],
            'assignment_status' => ['required', Rule::in(config('karyawan.options.assignment_status'))],
        ], ['assignment_end.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);

        // Cegah relasi ganda yang tidak disengaja: karyawan + proyek + tanggal mulai yang sama.
        $duplicate = EmployeeProject::where('employee_id', $employee->employee_id)
            ->where('project_id', $data['project_id'])
            ->when($data['assignment_start'] ?? null, fn ($q, $d) => $q->whereDate('assignment_start', $d), fn ($q) => $q->whereNull('assignment_start'))
            ->when($current, fn ($q) => $q->where('id', '!=', $current->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['project_id' => 'Penugasan ke proyek ini dengan tanggal mulai yang sama sudah ada.'])->errorBag($bag);
        }

        return $data;
    }

    private function back(Employee $employee, string $message)
    {
        return redirect()->route('employees.show', [$employee, 'tab' => 'pekerjaan'])->with('success', $message);
    }
}
