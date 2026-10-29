<?php

namespace App\Services\Excel;

use App\Models\Employee;
use App\Models\EmployeeProject;
use App\Models\ImportBatch;
use App\Models\Project;
use App\Models\UserAccount;
use App\Services\AccountService;
use App\Support\Audit;
use App\Support\EmployeeRules;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Import master karyawan dari Excel dalam dua tahap: analyze() untuk preview (tanpa menyimpan)
 * dan commit() untuk menyimpan baris valid dalam satu transaksi.
 *
 * Aturan: pencocokan berdasarkan NIK; NIK duplikat dalam file ditolak; baris invalid tidak
 * menimpa data; sel kosong tidak menghapus data yang sudah ada; profil yang tidak ada di file
 * tidak dihapus.
 */
class EmployeeImporter
{
    public function __construct(private AccountService $accounts) {}

    public function analyze(string $path): array
    {
        $book = IOFactory::createReaderForFile($path)->setReadDataOnly(true)->load($path);

        $employeeSheet = $book->getSheetByName('Karyawan') ?? $book->getSheet(0);
        $assignmentSheet = $book->getSheetByName('PenugasanProyek');

        [$headerMap, $rows] = $this->readSheet($employeeSheet, Columns::lookup(Columns::EMPLOYEE));
        $missing = array_diff(Columns::REQUIRED, $headerMap);
        if ($missing) {
            $labels = array_map(fn ($f) => array_search($f, Columns::EMPLOYEE, true), $missing);
            throw new RuntimeException('Kolom wajib tidak ditemukan pada sheet "'.$employeeSheet->getTitle().'": '.implode(', ', $labels).'.');
        }

        $result = [
            'sheet' => $employeeSheet->getTitle(),
            'columns' => array_values($headerMap),
            'unknown_columns' => [],
            'rows' => [],
            'mapping' => [],
            'notes' => [],
            'projects_new' => [],
            'assignments' => [],
        ];

        $existing = Employee::whereIn('nik', array_filter(array_map(fn ($r) => Cell::text($r['cells']['nik'] ?? null), $rows)))
            ->with(['education', 'languages', 'assignments'])->get()->keyBy('nik');
        $projects = Project::all();
        $nikCounts = array_count_values(array_filter(array_map(fn ($r) => Cell::text($r['cells']['nik'] ?? null), $rows)));
        $numericNik = 0;

        foreach ($rows as $row) {
            $parsed = $this->parseEmployeeRow($row, $existing, $result['mapping']);
            if (is_int($row['cells']['nik'] ?? null) || is_float($row['cells']['nik'] ?? null)) {
                $numericNik++;
            }
            if ($parsed['nik'] && ($nikCounts[$parsed['nik']] ?? 0) > 1) {
                $parsed['status'] = 'duplicate';
                $parsed['errors'][] = 'NIK muncul lebih dari satu kali di file. Perbaiki file lalu unggah ulang.';
            }
            $result['rows'][] = $parsed;
        }

        if ($numericNik > 0) {
            $result['notes'][] = "{$numericNik} NIK tersimpan sebagai angka di Excel. Bila NIK asli diawali angka 0, angka tersebut sudah hilang di file — format kolom NIK sebagai Text sebelum ekspor dari sistem lama.";
        }

        // Proyek dari kolom "Project" (penugasan utama, file lama).
        $validNiks = collect($result['rows'])->whereIn('status', ['new', 'update'])->pluck('nik')->all();
        foreach ($result['rows'] as $r) {
            if (in_array($r['status'], ['new', 'update'], true) && $r['project']) {
                $result['assignments'][] = [
                    'row' => $r['row'], 'sheet' => $result['sheet'], 'nik' => $r['nik'], 'project' => $r['project'],
                    'assignment_start' => null, 'assignment_end' => null, 'assignment_status' => 'Aktif', 'errors' => [],
                    'primary' => true,
                ];
            }
        }

        if ($assignmentSheet) {
            [$aHeaders, $aRows] = $this->readSheet($assignmentSheet, Columns::lookup(Columns::ASSIGNMENT));
            foreach ($aRows as $row) {
                $result['assignments'][] = $this->parseAssignmentRow($row, $assignmentSheet->getTitle(), $validNiks, $existing);
            }
        }

        foreach ($result['assignments'] as &$a) {
            if ($a['errors']) {
                continue;
            }
            $project = $this->findProject($projects, $a['project']);
            $a['project_exists'] = (bool) $project;
            if (! $project) {
                $result['projects_new'][mb_strtolower($a['project'])] = $a['project'];
            } elseif ($project->status !== 'Aktif') {
                $a['warnings'][] = "Proyek {$project->project_name} berstatus nonaktif.";
            }
        }
        unset($a);
        $result['projects_new'] = array_values($result['projects_new']);

        $result['summary'] = [
            'total' => count($result['rows']),
            'new' => collect($result['rows'])->where('status', 'new')->count(),
            'update' => collect($result['rows'])->where('status', 'update')->count(),
            'unchanged' => collect($result['rows'])->where('status', 'update')->where('changes', [])->count(),
            'duplicate' => collect($result['rows'])->where('status', 'duplicate')->count(),
            'invalid' => collect($result['rows'])->where('status', 'invalid')->count(),
            'warnings' => collect($result['rows'])->filter(fn ($r) => $r['warnings'])->count(),
            'assignments' => count($result['assignments']),
            'assignment_errors' => collect($result['assignments'])->filter(fn ($a) => $a['errors'])->count(),
        ];

        return $result;
    }

    /** @return array{0: array, 1: array} [field yang ditemukan, baris] */
    private function readSheet(Worksheet $sheet, array $lookup): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();
        $data = $sheet->rangeToArray('A1:'.$highestCol.$highestRow, null, false, false, false);
        $headers = array_shift($data) ?? [];

        $columns = [];
        foreach ($headers as $index => $header) {
            $key = Columns::normalizeHeader($header);
            if (isset($lookup[$key]) && ! in_array($lookup[$key], $columns, true)) {
                $columns[$index] = $lookup[$key];
            }
        }

        $rows = [];
        foreach ($data as $i => $values) {
            $cells = [];
            foreach ($columns as $index => $field) {
                $cells[$field] = $values[$index] ?? null;
            }
            if (collect($cells)->every(fn ($v) => Cell::isEmpty($v))) {
                continue;
            }
            $rows[] = ['row' => $i + 2, 'cells' => $cells];
        }

        return [array_values($columns), $rows];
    }

    private function parseEmployeeRow(array $row, $existing, array &$mapping): array
    {
        $cells = $row['cells'];
        $errors = [];
        $warnings = [];
        $values = [];

        foreach ($cells as $field => $raw) {
            if (str_starts_with($field, 'edu.') || in_array($field, ['project', 'languages'], true)) {
                continue;
            }
            if (in_array($field, Columns::DATE_FIELDS, true)) {
                [$date, $error] = Cell::date($raw);
                if ($error) {
                    $errors[] = $this->label($field).': '.$error.'.';
                }
                $values[$field] = $date;

                continue;
            }
            $text = Cell::text($raw);
            if ($text !== null && in_array($field, Columns::LONG_NUMBER_FIELDS, true) && (is_int($raw) || is_float($raw)) && strlen($text) >= 15) {
                $warnings[] = $this->label($field).' tersimpan sebagai angka di Excel; angka ≥15 digit berisiko kehilangan digit terakhir. Periksa ulang dan format kolom sebagai Text.';
            }
            if ($text !== null && in_array($field, ['no_ktp', 'kk'], true) && ctype_digit($text) && strlen($text) !== 16) {
                $warnings[] = $this->label($field).' berupa angka tetapi '.strlen($text).' digit (seharusnya 16) — tidak diimpor, periksa sumber data.';
                $text = null;
            }
            if ($text !== null && in_array($field, Columns::ENUM_FIELDS, true)) {
                $mapped = Cell::enum($field, $text);
                if ($mapped === null) {
                    $mapping[$field][$text] = ($mapping[$field][$text] ?? 0) + 1;
                    if ($field === 'jenis_kelamin') {
                        $errors[] = "Jenis kelamin \"{$text}\" tidak dikenali.";
                    } else {
                        $warnings[] = $this->label($field)." \"{$text}\" tidak dikenali — tidak diimpor, perlu pemetaan.";
                    }
                    $text = null;
                } else {
                    $text = $mapped;
                }
            }
            $values[$field] = $text;
        }

        $nik = $values['nik'] ?? null;
        $current = $nik ? $existing->get($nik) : null;
        $fileValues = array_filter($values, fn ($v) => $v !== null);

        // Gabungkan dengan data yang ada: sel kosong tidak menghapus nilai lama.
        $merged = $current
            ? array_merge($this->currentValues($current), $fileValues)
            : array_merge(['status_karyawan' => 'Aktif'], $fileValues);
        $merged = EmployeeRules::normalize($merged);

        $rules = Arr::except(EmployeeRules::rules(), ['nik']);
        $rules['nik'] = ['required', 'string', 'max:30'];
        $validator = validator($merged, $rules, EmployeeRules::messages());
        foreach ($validator->errors()->all() as $message) {
            $errors[] = $message;
        }
        $warnings = array_merge($warnings, EmployeeRules::warnings($merged));

        if (! $current && ! isset($fileValues['status_karyawan'])) {
            $warnings[] = 'StatusKaryawan tidak diisi — profil baru diset Aktif.';
        }

        // Pendidikan
        $education = null;
        $level = Cell::text($cells['edu.level'] ?? null);
        $institution = Cell::text($cells['edu.institution'] ?? null);
        if ($level || $institution) {
            $mappedLevel = Cell::enum('education_level', $level);
            $year = Cell::text($cells['edu.graduation_year'] ?? null);
            if ($level && ! $mappedLevel) {
                $mapping['education_level'][$level] = ($mapping['education_level'][$level] ?? 0) + 1;
                $warnings[] = "Jenjang pendidikan \"{$level}\" tidak dikenali — riwayat pendidikan tidak diimpor, perlu pemetaan.";
            } elseif (! $level || ! $institution) {
                $warnings[] = 'Pendidikan tidak lengkap (jenjang dan sekolah wajib) — tidak diimpor.';
            } elseif ($year !== null && (! ctype_digit($year) || (int) $year < 1950 || (int) $year > (int) date('Y'))) {
                $warnings[] = "Tahun lulus \"{$year}\" tidak valid — pendidikan diimpor tanpa tahun lulus.";
                $year = null;
            }
            if ($mappedLevel && $institution) {
                $education = [
                    'level' => $mappedLevel,
                    'major' => Cell::text($cells['edu.major'] ?? null),
                    'institution' => $institution,
                    'graduation_year' => $year ? (int) $year : null,
                ];
            }
        }

        $languages = [];
        if ($text = Cell::text($cells['languages'] ?? null)) {
            $languages = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;\/]+/', $text)))));
        }

        $changes = [];
        $important = [];
        if ($current) {
            $before = $this->currentValues($current);
            foreach (Employee::EDITABLE as $field) {
                if (array_key_exists($field, $fileValues) && ! $this->sameValue($field, $before[$field] ?? null, $merged[$field] ?? null)) {
                    $changes[] = $field;
                    if (in_array($field, Columns::IMPORTANT, true)) {
                        $important[$field] = [$before[$field] ?? null, $merged[$field] ?? null];
                    }
                }
            }
            if ($education && ! $this->hasEducation($current, $education)) {
                $changes[] = 'pendidikan';
            }
            $newLangs = array_diff(array_map('mb_strtolower', $languages), $current->languages->pluck('language_name')->map(fn ($l) => mb_strtolower($l))->all());
            if ($newLangs) {
                $changes[] = 'bahasa';
            }
        }

        return [
            'row' => $row['row'],
            'nik' => $nik,
            'nama' => $merged['nama'] ?? null,
            'status' => $errors ? 'invalid' : ($current ? 'update' : 'new'),
            'data' => $errors ? null : Arr::only($merged, Employee::EDITABLE),
            'file_fields' => array_keys($fileValues),
            'education' => $education,
            'languages' => $languages,
            'project' => Cell::text($cells['project'] ?? null),
            'errors' => $errors,
            'warnings' => array_values(array_unique($warnings)),
            'changes' => $changes,
            'important' => $important,
        ];
    }

    private function parseAssignmentRow(array $row, string $sheet, array $validNiks, $existing): array
    {
        $c = $row['cells'];
        $nik = Cell::text($c['nik'] ?? null);
        $project = Cell::text($c['project_code'] ?? null) ?? Cell::text($c['project_name'] ?? null);
        $errors = [];
        [$start, $e1] = Cell::date($c['assignment_start'] ?? null);
        [$end, $e2] = Cell::date($c['assignment_end'] ?? null);
        foreach (array_filter([$e1 ? 'TglMulai: '.$e1 : null, $e2 ? 'TglSelesai: '.$e2 : null]) as $e) {
            $errors[] = $e;
        }
        $status = Cell::text($c['assignment_status'] ?? null) ?? 'Aktif';
        $status = collect(config('karyawan.options.assignment_status'))->first(fn ($s) => mb_strtolower($s) === mb_strtolower($status));

        if (! $nik) {
            $errors[] = 'NIK kosong.';
        } elseif (! in_array($nik, $validNiks, true) && ! Employee::where('nik', $nik)->exists()) {
            $errors[] = 'NIK tidak ditemukan di sheet Karyawan yang valid maupun di database.';
        }
        if (! $project) {
            $errors[] = 'Kode/nama proyek kosong.';
        }
        if (! $status) {
            $errors[] = 'StatusPenugasan harus Aktif atau Selesai.';
        }
        if ($start && $end && $end < $start) {
            $errors[] = 'TglSelesai tidak boleh sebelum TglMulai.';
        }

        return [
            'row' => $row['row'], 'sheet' => $sheet, 'nik' => $nik, 'project' => $project,
            'assignment_start' => $start, 'assignment_end' => $end, 'assignment_status' => $status ?? 'Aktif',
            'errors' => $errors, 'primary' => false,
        ];
    }

    /**
     * Menyimpan hasil analisis. Semua perubahan dalam satu transaksi; bila terjadi error tak
     * terduga, seluruh batch dibatalkan sehingga data tidak tersimpan sebagian.
     */
    public function commit(string $path, ImportBatch $batch, bool $createProjects, bool $createAccounts): array
    {
        $analysis = $this->analyze($path);
        $created = 0;
        $updated = 0;
        $accounts = 0;
        $skippedAssignments = [];

        Audit::withContext(['import_batch' => $batch->id], function () use ($analysis, $createProjects, $createAccounts, &$created, &$updated, &$accounts, &$skippedAssignments) {
            DB::transaction(function () use ($analysis, $createProjects, $createAccounts, &$created, &$updated, &$accounts, &$skippedAssignments) {
                foreach ($analysis['rows'] as $r) {
                    if (! in_array($r['status'], ['new', 'update'], true)) {
                        continue;
                    }
                    $employee = Employee::where('nik', $r['nik'])->first();
                    if ($employee) {
                        $employee->fill(Arr::only($r['data'], $r['file_fields']));
                        if ($employee->isDirty()) {
                            $employee->save();
                        }
                        $updated++;
                    } else {
                        $employee = Employee::create($r['data']);
                        $created++;
                        if ($createAccounts && $employee->tgl_lahir && ! $employee->account()->exists()
                            && ! UserAccount::where('username', $employee->nik)->exists()) {
                            $this->accounts->createForEmployee($employee, 'Karyawan');
                            $accounts++;
                        }
                    }

                    $employee->load('education', 'languages');
                    if ($r['education'] && ! $this->hasEducation($employee, $r['education'])) {
                        $match = $employee->education->first(fn ($e) => $e->level === $r['education']['level'] && mb_strtolower($e->institution) === mb_strtolower($r['education']['institution']));
                        if ($match) {
                            $match->update(array_filter($r['education'], fn ($v) => $v !== null));
                        } else {
                            $employee->education()->create($r['education']);
                        }
                    }
                    $have = $employee->languages->pluck('language_name')->map(fn ($l) => mb_strtolower($l))->all();
                    foreach ($r['languages'] as $lang) {
                        if (! in_array(mb_strtolower($lang), $have, true)) {
                            $employee->languages()->create(['language_name' => mb_substr($lang, 0, 100)]);
                            $have[] = mb_strtolower($lang);
                        }
                    }
                }

                $projects = Project::all();
                foreach ($analysis['assignments'] as $a) {
                    if ($a['errors']) {
                        $skippedAssignments[] = ['row' => $a['row'], 'sheet' => $a['sheet'], 'nik' => $a['nik'], 'messages' => $a['errors']];

                        continue;
                    }
                    $project = $this->findProject($projects, $a['project']);
                    if (! $project) {
                        if (! $createProjects) {
                            $skippedAssignments[] = ['row' => $a['row'], 'sheet' => $a['sheet'], 'nik' => $a['nik'], 'messages' => ["Proyek \"{$a['project']}\" belum ada di master dan pembuatan proyek baru tidak dikonfirmasi."]];

                            continue;
                        }
                        $project = Project::create(['project_name' => mb_substr($a['project'], 0, 200), 'status' => 'Aktif']);
                        $projects->push($project);
                    }
                    $employee = Employee::where('nik', $a['nik'])->first();
                    if (! $employee) {
                        continue;
                    }
                    $exists = EmployeeProject::where('employee_id', $employee->employee_id)->where('project_id', $project->id)
                        ->when($a['primary'], fn ($q) => $q, fn ($q) => $a['assignment_start']
                            ? $q->whereDate('assignment_start', $a['assignment_start'])
                            : $q->whereNull('assignment_start'))
                        ->exists();
                    if (! $exists) {
                        $employee->assignments()->create([
                            'project_id' => $project->id,
                            'assignment_start' => $a['assignment_start'],
                            'assignment_end' => $a['assignment_end'],
                            'assignment_status' => $a['assignment_status'],
                        ]);
                    }
                }
            });
        });

        $failedRows = collect($analysis['rows'])->whereIn('status', ['invalid', 'duplicate']);
        $errorSummary = [
            'rows' => $failedRows->map(fn ($r) => ['row' => $r['row'], 'sheet' => $analysis['sheet'], 'nik' => $r['nik'], 'status' => $r['status'], 'messages' => $r['errors']])->values()->all(),
            'warnings' => collect($analysis['rows'])->filter(fn ($r) => $r['warnings'] && in_array($r['status'], ['new', 'update']))
                ->map(fn ($r) => ['row' => $r['row'], 'sheet' => $analysis['sheet'], 'nik' => $r['nik'], 'status' => 'peringatan', 'messages' => $r['warnings']])->values()->all(),
            'assignments' => $skippedAssignments,
            'mapping' => $analysis['mapping'],
            'notes' => $analysis['notes'],
            'accounts_created' => $accounts,
        ];

        $batch->update([
            'total_rows' => $analysis['summary']['total'],
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failedRows->count(),
            'status' => 'Selesai',
            'error_summary' => $errorSummary,
            'processed_at' => now(),
        ]);
        Audit::log('import_batch', $batch->id, 'Import', null, null, [
            'file' => $batch->file_name, 'created' => $created, 'updated' => $updated, 'failed' => $failedRows->count(),
        ]);

        return $errorSummary;
    }

    private function findProject($projects, ?string $name): ?Project
    {
        if (! $name) {
            return null;
        }
        $needle = mb_strtolower(trim($name));

        return $projects->first(fn ($p) => mb_strtolower($p->project_name) === $needle || ($p->project_code && mb_strtolower($p->project_code) === $needle));
    }

    private function hasEducation(Employee $employee, array $education): bool
    {
        return $employee->education->contains(fn ($e) => $e->level === $education['level']
            && mb_strtolower($e->institution) === mb_strtolower($education['institution'])
            && ($education['graduation_year'] === null || $e->graduation_year === $education['graduation_year']));
    }

    private function currentValues(Employee $employee): array
    {
        $values = [];
        foreach (Employee::EDITABLE as $field) {
            $value = $employee->getAttribute($field);
            $values[$field] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : ($value === null ? null : (string) $value);
        }

        return $values;
    }

    private function sameValue(string $field, mixed $a, mixed $b): bool
    {
        if (in_array($field, ['tinggi_badan_cm', 'berat_badan_kg'], true) && is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return (string) $a === (string) $b;
    }

    private function label(string $field): string
    {
        return array_search($field, Columns::EMPLOYEE, true) ?: (array_search($field, Columns::ASSIGNMENT, true) ?: $field);
    }
}
