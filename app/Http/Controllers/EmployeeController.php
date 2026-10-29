<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Support\EmployeeRules;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public const SECTIONS = [
        'identitas' => [
            'nik', 'nama', 'nama_panggilan', 'jenis_kelamin', 'tgl_lahir', 'tempat_lahir', 'kewarganegaraan', 'agama',
            'status_nikah', 'gol_darah', 'tinggi_badan_cm', 'berat_badan_kg', 'alamat', 'no_hp', 'email', 'no_ktp',
            'kk', 'npwp', 'no_bpjs_tk', 'no_bpjs_kes', 'status_ptkp', 'nama_ibu',
        ],
        'pekerjaan' => [
            'kode_jbt', 'jobtitle', 'kategori_karyawan', 'status_awal', 'tgl_masuk', 'kontrak_ke', 'akhir_kontrak',
        ],
    ];

    public const TABS = [
        'identitas' => 'Identitas & Administrasi',
        'pekerjaan' => 'Pekerjaan & Kontrak',
        'pendidikan' => 'Pendidikan & Bahasa',
        'foto' => 'Foto',
        'training' => 'TRAINING',
        'pengalaman' => 'Pengalaman Kerja',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status', 'project', 'jobtitle', 'kontrak']);
        $query = $this->filteredQuery($filters)->with('activeProjects')->orderBy('nama');

        return view('employees.index', [
            'employees' => $query->paginate(25)->withQueryString(),
            'filters' => $filters,
            'projects' => Project::orderBy('project_name')->get(['id', 'project_name', 'project_code']),
            'jobtitles' => Employee::query()->distinct()->orderBy('jobtitle')->pluck('jobtitle'),
        ]);
    }

    public static function filteredQuery(array $filters)
    {
        $query = Employee::query();

        if ($term = trim($filters['q'] ?? '')) {
            $like = '%'.mb_strtolower($term).'%';
            $query->where(fn ($q) => $q->where('nik', 'like', '%'.$term.'%')->orWhereRaw('LOWER(nama) LIKE ?', [$like]));
        }
        if (! empty($filters['status'])) {
            $query->where('status_karyawan', $filters['status']);
        }
        if (! empty($filters['project'])) {
            $query->whereHas('assignments', fn ($q) => $q->where('project_id', $filters['project'])->where('assignment_status', 'Aktif'));
        }
        if (! empty($filters['jobtitle'])) {
            $query->where('jobtitle', $filters['jobtitle']);
        }
        $today = Carbon::today();
        match ($filters['kontrak'] ?? null) {
            '30', '60', '90' => $query->whereBetween('akhir_kontrak', [$today, $today->copy()->addDays((int) $filters['kontrak'])]),
            'lewat' => $query->where('akhir_kontrak', '<', $today),
            'kosong' => $query->whereNull('akhir_kontrak'),
            default => null,
        };

        return $query;
    }

    public function create()
    {
        return view('employees.create', ['employee' => new Employee(['status_karyawan' => 'Aktif'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, array_merge(self::SECTIONS['identitas'], self::SECTIONS['pekerjaan']), null, 'profil');
        $data['status_karyawan'] = 'Aktif';
        $employee = Employee::create($data);

        return redirect()->route('employees.show', [$employee, 'tab' => 'pendidikan'])
            ->with('success', "Profil {$employee->nama} berhasil dibuat. Lanjutkan mengisi pendidikan, foto, dan TRAINING.")
            ->with('warning', EmployeeRules::warnings($data) ?: null);
    }

    public function show(Request $request, Employee $employee)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'identitas';
        $employee->load([
            'education' => fn ($q) => $q->orderByRaw('graduation_year IS NULL')->orderByDesc('graduation_year'),
            'languages',
            'trainings' => fn ($q) => $q->orderByRaw('year IS NULL')->orderByDesc('year'),
            'experiences' => fn ($q) => $q->latestFirst()->with('updater.employee'),
            'assignments' => fn ($q) => $q->with('project')->orderBy('assignment_status')->orderByDesc('assignment_start'),
            'account',
        ]);

        return view('employees.show', [
            'employee' => $employee,
            'tab' => $tab,
            'tabs' => self::TABS,
            'projects' => Project::active()->orderBy('project_name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $section = $request->input('section');
        abort_unless(isset(self::SECTIONS[$section]), 422);

        $data = $this->validated($request, self::SECTIONS[$section], $employee, $section);
        $employee->fill($data);
        $changed = array_keys($employee->getDirty());
        $employee->save();

        return redirect()->route('employees.show', [$employee, 'tab' => $section])
            ->with('success', $changed ? 'Perubahan tersimpan.' : 'Tidak ada perubahan.')
            ->with('warning', EmployeeRules::warnings($employee->only(array_merge(['status_karyawan'], self::SECTIONS[$section]))) ?: null);
    }

    public function toggleStatus(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            $employee->status_karyawan = $employee->isActive() ? 'Nonaktif' : 'Aktif';
            $employee->save();
            if (! $employee->isActive() && $employee->account?->isActive()) {
                $employee->account->update(['account_status' => 'Nonaktif']);
            }
        });

        $message = $employee->isActive()
            ? 'Karyawan diaktifkan kembali. Aktifkan akunnya di menu Akun Pengguna bila diperlukan.'
            : 'Karyawan dinonaktifkan. Data tetap tersimpan; akun login ikut dinonaktifkan.';

        return back()->with('success', $message);
    }

    private function validated(Request $request, array $fields, ?Employee $employee, string $form): array
    {
        $input = EmployeeRules::normalize(Arr::only($request->all(), $fields));
        $rules = Arr::only(EmployeeRules::rules($employee?->employee_id), $fields);
        if (in_array('akhir_kontrak', $fields) && ! in_array('tgl_masuk', $fields)) {
            $input['tgl_masuk'] = $employee?->tgl_masuk?->format('Y-m-d');
        }

        $validator = validator($input, $rules, EmployeeRules::messages());
        if ($validator->fails()) {
            throw (new ValidationException($validator))->errorBag($form)
                ->redirectTo(url()->previous());
        }

        return Arr::only($validator->validated(), $fields);
    }
}
