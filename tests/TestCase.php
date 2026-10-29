<?php

namespace Tests;

use App\Models\Employee;
use App\Models\UserAccount;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function hr(): UserAccount
    {
        return UserAccount::create([
            'username' => 'HR'.random_int(1000, 9999), 'display_name' => 'HR Test', 'role' => 'HR',
            'password_hash' => 'Rahasia#2026', 'must_change_password' => false, 'account_status' => 'Aktif',
        ]);
    }

    protected function employeeUser(?Employee $employee = null, bool $mustChange = false): UserAccount
    {
        $employee ??= Employee::factory()->create();

        return UserAccount::create([
            'employee_id' => $employee->employee_id, 'username' => $employee->nik, 'role' => 'Karyawan',
            'password_hash' => 'Karyawan#2026', 'must_change_password' => $mustChange, 'account_status' => 'Aktif',
        ]);
    }
}
