<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\UserAccount;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Provisioning akun HR/admin secara privat (password diketik saat setup, tidak disimpan di kode).
 */
class CreateHrAccount extends Command
{
    protected $signature = 'karyawan:buat-hr {username : NIK admin/HR} {--nama= : Nama tampilan} {--temporary : Wajib ganti password saat login pertama}';

    protected $description = 'Membuat akun HR pusat dengan password yang diketik secara interaktif';

    public function handle(): int
    {
        $username = trim($this->argument('username'));
        if (UserAccount::where('username', $username)->exists()) {
            $this->error("Username {$username} sudah ada.");

            return self::FAILURE;
        }

        $password = $this->secret('Password (min. 12 karakter, huruf besar/kecil, angka, simbol)');
        $confirm = $this->secret('Ulangi password');
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $confirm], [
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $employee = Employee::where('nik', $username)->first();
        $account = UserAccount::create([
            'employee_id' => $employee?->employee_id,
            'username' => $username,
            'display_name' => $this->option('nama') ?: ($employee?->nama ?? 'HR Pusat'),
            'role' => 'HR',
            'password_hash' => $password,
            'must_change_password' => (bool) $this->option('temporary'),
            'account_status' => 'Aktif',
        ]);
        Audit::log('user_account', $account->id, 'ProvisionHR', null, null, ['via' => 'console']);

        $this->info("Akun HR {$username} berhasil dibuat.".($employee ? ' Terhubung ke profil karyawan.' : ''));

        return self::SUCCESS;
    }
}
