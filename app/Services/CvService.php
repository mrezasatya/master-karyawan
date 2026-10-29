<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\Format;
use App\Support\Photo;
use Dompdf\Dompdf;
use Dompdf\Options;

class CvService
{
    /** Muat relasi yang dibutuhkan CV dengan urutan terbaru lebih dulu. */
    public function load(Employee $employee): Employee
    {
        return $employee->load([
            'education' => fn ($q) => $q->orderByRaw('graduation_year IS NULL')->orderByDesc('graduation_year'),
            'languages',
            'experiences' => fn ($q) => $q->latestFirst(),
            'trainings' => fn ($q) => $q->orderByRaw('year IS NULL')->orderByDesc('year')->orderBy('item_name'),
        ]);
    }

    /**
     * Kelengkapan CV. "required" memblokir unduhan CV final; "warnings" hanya peringatan.
     * Nilai kosong tidak pernah diisi/dikarang oleh sistem.
     */
    public function checklist(Employee $employee): array
    {
        $required = [
            'Name' => filled($employee->nama),
            'Sex' => filled($employee->jenis_kelamin),
            'Date of Birth' => filled($employee->tgl_lahir),
            'Citizen (kewarganegaraan)' => filled($employee->kewarganegaraan),
            'Position (job title)' => filled($employee->jobtitle),
            'Kontak: No. HP atau email' => filled($employee->no_hp) || filled($employee->email),
        ];
        $warnings = [
            'Foto' => filled($employee->foto_path) && Photo::dataUri($employee->foto_path) !== null,
            'Pendidikan' => $employee->education->isNotEmpty(),
            'Tahun lulus pada semua pendidikan' => $employee->education->every(fn ($e) => filled($e->graduation_year)),
            'Pengalaman kerja' => $employee->experiences->isNotEmpty(),
            'Bahasa' => $employee->languages->isNotEmpty(),
            'TRAINING' => $employee->trainings->isNotEmpty(),
        ];

        return [
            'required' => $required,
            'warnings' => $warnings,
            'ready' => ! in_array(false, $required, true),
        ];
    }

    public function viewData(Employee $employee): array
    {
        return [
            'e' => $employee,
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-bukaka.png'))),
            'photo' => Photo::dataUri($employee->foto_path),
            'cvLevel' => fn (?string $level) => config('karyawan.cv_education_labels')[$level] ?? $level,
            'personal' => [
                'Name' => $employee->nama,
                'Sex' => Format::cvSex($employee->jenis_kelamin),
                'Date of Birth' => Format::cvDate($employee->tgl_lahir),
                'Citizen' => $employee->kewarganegaraan,
                'Position' => $employee->jobtitle,
                'Phone' => $employee->no_hp,
                'Email' => $employee->email,
                'Language' => $employee->languages->pluck('language_name')->join(', '),
            ],
        ];
    }

    public function html(Employee $employee): string
    {
        return view('cv.template', $this->viewData($this->load($employee)))->render();
    }

    public function pdf(Employee $employee): string
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false); // tidak memuat resource eksternal
        $options->setDefaultFont('Helvetica');
        $options->setDefaultMediaType('print');
        $options->setChroot(base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($employee), 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function fileName(Employee $employee): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '_', $employee->nama);

        return 'CV_'.$employee->nik.'_'.trim($name, '_').'.pdf';
    }
}
