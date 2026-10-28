<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\WorkExperience;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Pengalaman kerja dapat dikelola karyawan (hanya milik sendiri) dan HR (semua karyawan).
 * Perubahan langsung tersimpan tanpa persetujuan; pelaku dan waktu dicatat.
 */
class ExperienceController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->isHr() && $request->filled('employee_id')) {
            $employee = Employee::findOrFail($request->integer('employee_id'));
        } else {
            abort_unless($user->employee_id, 403, 'Akun ini tidak terhubung ke profil karyawan.');
            if ($request->filled('employee_id') && (int) $request->input('employee_id') !== $user->employee_id) {
                $this->denied($request, 'store_other_employee', (int) $request->input('employee_id'));
            }
            $employee = $user->employee;
        }

        $data = $this->validated($request, 'exp-new');
        $experience = $employee->experiences()->create($data);

        return $this->respond($request, 'Pengalaman kerja tersimpan.', $experience);
    }

    public function update(Request $request, WorkExperience $experience)
    {
        $this->authorizeOwner($request, $experience);
        $experience->update($this->validated($request, 'exp-'.$experience->id));

        return $this->respond($request, 'Perubahan pengalaman kerja tersimpan.', $experience);
    }

    public function destroy(Request $request, WorkExperience $experience)
    {
        $this->authorizeOwner($request, $experience);
        $experience->update(['updated_by' => $request->user()->id]);
        $experience->delete(); // soft delete — riwayat tetap ada untuk audit

        return $this->respond($request, 'Pengalaman kerja diarsipkan.', $experience);
    }

    private function authorizeOwner(Request $request, WorkExperience $experience): void
    {
        $user = $request->user();
        if ($user->isHr() || ($user->employee_id && $experience->employee_id === $user->employee_id)) {
            return;
        }
        $this->denied($request, 'access_other_experience', $experience->employee_id, $experience->id);
    }

    private function denied(Request $request, string $event, ?int $employeeId, ?int $experienceId = null): never
    {
        Audit::security($event, array_filter(['target_employee_id' => $employeeId, 'experience_id' => $experienceId]));
        abort(403, 'Anda hanya dapat mengubah pengalaman kerja milik sendiri.');
    }

    private function validated(Request $request, string $bag): array
    {
        $year = (int) now()->format('Y');
        $maxYear = $request->boolean('confirm_future') ? $year + 5 : $year;
        $isCurrent = $request->boolean('is_current');

        $validator = validator($request->all(), [
            'experience_type' => ['required', Rule::in(config('karyawan.options.experience_type'))],
            'company_name' => ['required', 'string', 'max:200'],
            'project_name' => ['nullable', 'string', 'max:200'],
            'position' => ['required', 'string', 'max:150'],
            'start_month' => ['required', 'integer', 'between:1,12'],
            'start_year' => ['required', 'integer', 'min:1950', 'max:'.$maxYear],
            'end_month' => $isCurrent ? ['prohibited'] : ['required', 'integer', 'between:1,12'],
            'end_year' => $isCurrent ? ['prohibited'] : ['required', 'integer', 'min:1950', 'max:'.$maxYear],
            'responsibilities' => ['nullable', 'string', 'max:2000'],
        ], [
            'start_year.max' => 'Tahun mulai melewati tahun berjalan. Centang konfirmasi bila memang benar.',
            'end_year.max' => 'Tahun selesai melewati tahun berjalan. Centang konfirmasi bila memang benar.',
            'end_month.required' => 'Bulan selesai wajib diisi, atau centang "Masih bekerja di sini".',
            'end_year.required' => 'Tahun selesai wajib diisi, atau centang "Masih bekerja di sini".',
            'end_month.prohibited' => 'Kosongkan bulan selesai karena pengalaman masih berlangsung.',
            'end_year.prohibited' => 'Kosongkan tahun selesai karena pengalaman masih berlangsung.',
        ]);

        $validator->after(function (Validator $v) use ($isCurrent) {
            $d = $v->getData();
            if ($isCurrent || $v->errors()->hasAny(['start_month', 'start_year', 'end_month', 'end_year'])) {
                return;
            }
            if ((int) $d['start_year'] * 12 + (int) $d['start_month'] > (int) $d['end_year'] * 12 + (int) $d['end_month']) {
                $v->errors()->add('end_year', 'Periode selesai tidak boleh sebelum periode mulai.');
            }
        });

        if ($validator->fails()) {
            throw (new ValidationException($validator))->errorBag($bag);
        }

        $data = $validator->validated();
        $data['is_current'] = $isCurrent;
        if ($isCurrent) {
            $data['end_month'] = null;
            $data['end_year'] = null;
        }
        $data['project_name'] ??= null;
        $data['responsibilities'] ??= null;

        return $data;
    }

    private function respond(Request $request, string $message, WorkExperience $experience)
    {
        $redirect = $request->user()->isHr() && $experience->employee_id !== $request->user()->employee_id
            ? route('employees.show', [$experience->employee_id, 'tab' => 'pengalaman'])
            : route('portal.index');

        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['message' => $message, 'redirect' => $redirect]);
        }

        return redirect($redirect)->with('success', $message);
    }
}
