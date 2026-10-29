<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\UserAccount;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    public function index(Request $request)
    {
        $accounts = UserAccount::query()->with('employee')
            ->when($request->query('q'), function ($q, $term) {
                $like = '%'.mb_strtolower($term).'%';
                $q->where(fn ($w) => $w->where('username', 'like', "%$term%")
                    ->orWhereRaw('LOWER(display_name) LIKE ?', [$like])
                    ->orWhereHas('employee', fn ($e) => $e->whereRaw('LOWER(nama) LIKE ?', [$like])));
            })
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->when($request->query('status'), fn ($q, $s) => $q->where('account_status', $s))
            ->orderBy('username')
            ->paginate(25)->withQueryString();

        return view('accounts.index', [
            'accounts' => $accounts,
            'withoutAccount' => Employee::active()->doesntHave('account')->count(),
            'withoutAccountNoDob' => Employee::active()->doesntHave('account')->whereNull('tgl_lahir')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('acc', [
            'nik' => ['required', 'string', Rule::exists('employees', 'nik')],
            'role' => ['required', Rule::in(config('karyawan.options.role'))],
        ], ['nik.exists' => 'NIK tidak ditemukan pada data karyawan.']);

        $employee = Employee::where('nik', trim($data['nik']))->firstOrFail();
        if ($employee->account) {
            return back()->withErrors(['nik' => 'Karyawan ini sudah memiliki akun.'], 'acc')->withInput();
        }
        if (UserAccount::where('username', $employee->nik)->exists()) {
            return back()->withErrors(['nik' => 'Username dengan NIK ini sudah dipakai akun lain.'], 'acc')->withInput();
        }

        [$account, $password, $note, $show] = $this->accounts->createForEmployee($employee, $data['role']);

        return back()->with('credential', ['username' => $account->username, 'password' => $show ? $password : null, 'note' => $note, 'show' => $show])
            ->with('success', "Akun {$account->username} dibuat.");
    }

    /** Membuat akun untuk semua karyawan aktif yang belum punya akun dan sudah memiliki tanggal lahir. */
    public function bulk()
    {
        abort_unless(config('karyawan.password_awal') === 'tanggal_lahir', 422, 'Pembuatan massal hanya tersedia pada mode password tanggal lahir.');

        $created = 0;
        Employee::active()->doesntHave('account')->whereNotNull('tgl_lahir')
            ->whereNotIn('nik', UserAccount::select('username'))
            ->chunkById(200, function ($employees) use (&$created) {
                foreach ($employees as $employee) {
                    $this->accounts->createForEmployee($employee, 'Karyawan');
                    $created++;
                }
            }, 'employee_id');

        return back()->with('success', "{$created} akun karyawan dibuat. Password awal = tanggal lahir (DDMMYY), wajib diganti saat login pertama.");
    }

    public function reset(UserAccount $account)
    {
        [$password, $note, $show] = $this->accounts->resetPassword($account);

        return back()->with('credential', ['username' => $account->username, 'password' => $show ? $password : null, 'note' => $note, 'show' => $show])
            ->with('success', "Password {$account->username} direset.");
    }

    public function toggleStatus(Request $request, UserAccount $account)
    {
        if ($account->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }
        $account->update(['account_status' => $account->isActive() ? 'Nonaktif' : 'Aktif']);

        return back()->with('success', "Akun {$account->username} sekarang {$account->account_status}.");
    }
}
