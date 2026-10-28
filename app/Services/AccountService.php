<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\UserAccount;
use App\Support\Audit;
use Illuminate\Support\Str;

class AccountService
{
    /**
     * Menentukan password awal. Mengembalikan [password, keterangan, tampilkanKeHr].
     * Password tidak pernah disimpan dalam bentuk asli; hanya hash.
     */
    public function initialPassword(?Employee $employee, string $role): array
    {
        if ($role === 'Karyawan' && config('karyawan.password_awal') === 'tanggal_lahir' && $employee?->tgl_lahir) {
            return [$employee->tgl_lahir->format('dmy'), 'Password awal = tanggal lahir (DDMMYY).', false];
        }

        return [$this->randomPassword(), 'Password sementara acak — sampaikan secara langsung/pribadi kepada pemilik akun. Password ini hanya ditampilkan sekali.', true];
    }

    public function randomPassword(): string
    {
        // Hindari karakter yang mirip (0/O, 1/l/I) agar mudah dibacakan.
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 10; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    /** @return array{0: UserAccount, 1: string, 2: string, 3: bool} */
    public function createForEmployee(Employee $employee, string $role = 'Karyawan'): array
    {
        [$password, $note, $show] = $this->initialPassword($employee, $role);

        $account = UserAccount::create([
            'employee_id' => $employee->employee_id,
            'username' => $employee->nik,
            'role' => $role,
            'password_hash' => $password,
            'must_change_password' => true,
            'account_status' => 'Aktif',
        ]);

        return [$account, $password, $note, $show];
    }

    /** @return array{0: string, 1: string, 2: bool} */
    public function resetPassword(UserAccount $account): array
    {
        [$password, $note, $show] = $this->initialPassword($account->employee, $account->role);
        $account->forceFill(['password_hash' => $password, 'must_change_password' => true])->save();
        Audit::log('user_account', $account->id, 'PasswordReset');

        return [$password, $note, $show];
    }

    public static function looksLikeBirthDate(string $password, ?Employee $employee): bool
    {
        if (! $employee?->tgl_lahir) {
            return false;
        }
        $d = $employee->tgl_lahir;
        $variants = [$d->format('dmy'), $d->format('dmY'), $d->format('d/m/y'), $d->format('d/m/Y'), $d->format('Ymd'), $d->format('d-m-Y')];

        return in_array($password, $variants, true) || Str::contains($password, $d->format('dmy'));
    }
}
