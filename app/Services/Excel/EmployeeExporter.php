<?php

namespace App\Services\Excel;

use App\Models\EmployeeProject;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export master karyawan ke format template standar (sheet Karyawan + PenugasanProyek).
 * Semua kolom non-tanggal ditulis sebagai Text agar NIK/nomor tidak berubah.
 */
class EmployeeExporter
{
    public function build(?Builder $query): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->getProperties()->setTitle('Master Data Karyawan Bukaka')->setCreator('Master Karyawan Bukaka');

        $sheet = $book->getActiveSheet()->setTitle('Karyawan');
        $headers = array_keys(Columns::EMPLOYEE);
        $this->writeHeader($sheet, $headers);
        $fields = array_values(Columns::EMPLOYEE);

        $assignSheet = $book->createSheet()->setTitle('PenugasanProyek');
        $this->writeHeader($assignSheet, array_keys(Columns::ASSIGNMENT));

        if ($query) {
            $r = 2;
            $ar = 2;
            $query->with(['education' => fn ($q) => $q->orderByRaw('graduation_year IS NULL')->orderByDesc('graduation_year'), 'languages', 'assignments.project'])
                ->orderBy('nik')
                ->chunk(300, function ($employees) use ($sheet, $assignSheet, $fields, &$r, &$ar) {
                    foreach ($employees as $e) {
                        $edu = $e->education->first();
                        $primary = $e->assignments->firstWhere('assignment_status', 'Aktif');
                        foreach ($fields as $i => $field) {
                            $value = match ($field) {
                                'edu.level' => $edu?->level,
                                'edu.major' => $edu?->major,
                                'edu.institution' => $edu?->institution,
                                'edu.graduation_year' => $edu?->graduation_year,
                                'project' => $primary?->project->project_name,
                                'languages' => $e->languages->pluck('language_name')->join(', '),
                                default => $e->getAttribute($field),
                            };
                            $this->writeCell($sheet, $i + 1, $r, $value, in_array($field, Columns::DATE_FIELDS, true));
                        }
                        $r++;

                        foreach ($e->assignments as $a) {
                            $this->writeAssignment($assignSheet, $ar++, $e->nik, $a);
                        }
                    }
                });
        }

        $this->autosize($sheet, count($headers));
        $this->autosize($assignSheet, count(Columns::ASSIGNMENT));
        $this->instructions($book);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    public function save(Spreadsheet $book, string $path): void
    {
        (new Xlsx($book))->save($path);
    }

    private function writeAssignment(Worksheet $sheet, int $row, string $nik, EmployeeProject $a): void
    {
        $values = [$nik, $a->project->project_code, $a->project->project_name, $a->assignment_start, $a->assignment_end, $a->assignment_status];
        foreach ($values as $i => $value) {
            $this->writeCell($sheet, $i + 1, $row, $value, $value instanceof \DateTimeInterface || in_array($i, [3, 4], true));
        }
    }

    private function writeHeader(Worksheet $sheet, array $headers): void
    {
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $last = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$last}1")->getFill()->setFillType('solid')->getStartColor()->setRGB('006599');
        $sheet->freezePane('B2');
        // Format kolom sebagai Text agar entri manual juga aman (leading zero).
        $sheet->getStyle("A2:{$last}5000")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
    }

    private function writeCell(Worksheet $sheet, int $col, int $row, mixed $value, bool $isDate): void
    {
        if ($value === null || $value === '') {
            return;
        }
        if ($isDate && $value instanceof \DateTimeInterface) {
            $sheet->setCellValue([$col, $row], ExcelDate::PHPToExcel($value));
            $sheet->getStyle([$col, $row])->getNumberFormat()->setFormatCode('dd/mm/yyyy');

            return;
        }
        $sheet->setCellValueExplicit([$col, $row], (string) $value, DataType::TYPE_STRING);
    }

    private function autosize(Worksheet $sheet, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    private function instructions(Spreadsheet $book): void
    {
        $s = $book->createSheet()->setTitle('Petunjuk');
        $lines = [
            'PETUNJUK IMPORT MASTER DATA KARYAWAN',
            '',
            'Sheet "Karyawan": satu baris per NIK. Kolom wajib: NIK, Nama, JenisKelamin, Jobtitle, TglMasuk.',
            'Sheet "PenugasanProyek" (opsional): satu baris per penugasan karyawan-proyek. Isi KodeProyek atau NamaProyek.',
            'Kolom "Project" pada sheet Karyawan diperlakukan sebagai penugasan utama (status Aktif).',
            'Format tanggal: DD/MM/YYYY atau sel bertipe tanggal Excel.',
            'NIK, No. KTP, KK, NPWP, BPJS, dan No. HP harus berformat Text agar angka nol di depan/digit akhir tidak hilang.',
            'Pencocokan data berdasarkan NIK. Sel kosong TIDAK menghapus data yang sudah ada. Profil yang tidak ada di file tidak dihapus.',
            'NIK duplikat dalam satu file ditolak sampai diperbaiki.',
            'StatusKaryawan: Aktif/Nonaktif (profil baru tanpa nilai diset Aktif). Status: kategori karyawan, mis. Karyawan Proyek.',
            'Pilihan JenisKelamin: '.implode(', ', config('karyawan.options.jenis_kelamin')),
            'Pilihan Agama: '.implode(', ', config('karyawan.options.agama')),
            'Pilihan StatusNikah: '.implode(', ', config('karyawan.options.status_nikah')),
            'Pilihan Status PTKP: '.implode(', ', config('karyawan.options.status_ptkp')),
            'Pilihan GolDarah: '.implode(', ', config('karyawan.options.gol_darah')),
            'Pilihan Pendidikan: '.implode(', ', config('karyawan.options.education_level')),
            'Bahasa: pisahkan dengan koma, mis. "Bahasa Indonesia, Bahasa Inggris".',
            'StatusPenugasan: Aktif / Selesai.',
        ];
        foreach ($lines as $i => $line) {
            $s->setCellValue([1, $i + 1], $line);
        }
        $s->getStyle('A1')->getFont()->setBold(true);
        $s->getColumnDimension('A')->setWidth(120);
    }
}
