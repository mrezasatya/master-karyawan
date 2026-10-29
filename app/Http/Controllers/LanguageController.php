<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLanguage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $request->validateWithBag('lang', [
            'choice' => ['required', Rule::in([...config('karyawan.options.languages'), 'Lainnya'])],
            'other' => ['required_if:choice,Lainnya', 'nullable', 'string', 'max:100'],
        ], ['other.required_if' => 'Tulis nama bahasa bila memilih Lainnya.', 'choice.required' => 'Pilih bahasa.']);

        $name = $data['choice'] === 'Lainnya' ? trim($data['other']) : $data['choice'];
        $exists = $employee->languages()->whereRaw('LOWER(language_name) = ?', [mb_strtolower($name)])->exists();
        if ($exists) {
            return back()->withErrors(['choice' => "Bahasa {$name} sudah tercatat."], 'lang')->withInput(['_form' => 'lang']);
        }
        $employee->languages()->create(['language_name' => $name]);

        return redirect()->route('employees.show', [$employee, 'tab' => 'pendidikan'])->with('success', "Bahasa {$name} ditambahkan.");
    }

    public function destroy(EmployeeLanguage $language)
    {
        $employeeId = $language->employee_id;
        $language->delete();

        return redirect()->route('employees.show', [$employeeId, 'tab' => 'pendidikan'])->with('success', 'Bahasa dihapus.');
    }
}
