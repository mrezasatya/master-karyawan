<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducationController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $employee->education()->create($this->validated($request, 'edu-new'));

        return $this->back($employee, 'Riwayat pendidikan ditambahkan.');
    }

    public function update(Request $request, EmployeeEducation $education)
    {
        $education->update($this->validated($request, 'edu-'.$education->id));

        return $this->back($education->employee, 'Riwayat pendidikan diperbarui.');
    }

    public function destroy(EmployeeEducation $education)
    {
        $employee = $education->employee;
        $education->delete();

        return $this->back($employee, 'Riwayat pendidikan dihapus.');
    }

    private function validated(Request $request, string $bag): array
    {
        return $request->validateWithBag($bag, [
            'level' => ['required', Rule::in(config('karyawan.options.education_level'))],
            'major' => ['nullable', 'string', 'max:150'],
            'institution' => ['required', 'string', 'max:200'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
        ]);
    }

    private function back(Employee $employee, string $message)
    {
        return redirect()->route('employees.show', [$employee, 'tab' => 'pendidikan'])->with('success', $message);
    }
}
