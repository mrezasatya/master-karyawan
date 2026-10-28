<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TrainingCertification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $employee->trainings()->create($this->validated($request, 'trn-new'));

        return $this->back($employee, 'TRAINING ditambahkan.');
    }

    public function update(Request $request, TrainingCertification $training)
    {
        $training->update($this->validated($request, 'trn-'.$training->id));

        return $this->back($training->employee, 'TRAINING diperbarui.');
    }

    public function destroy(TrainingCertification $training)
    {
        $employee = $training->employee;
        $training->delete();

        return $this->back($employee, 'TRAINING dihapus.');
    }

    private function validated(Request $request, string $bag): array
    {
        return $request->validateWithBag($bag, [
            'item_type' => ['required', Rule::in(config('karyawan.options.training_type'))],
            'item_name' => ['required', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'organizer' => ['nullable', 'string', 'max:200'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ], ['expiry_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh sebelum tanggal terbit.']);
    }

    private function back(Employee $employee, string $message)
    {
        return redirect()->route('employees.show', [$employee, 'tab' => 'training'])->with('success', $message);
    }
}
