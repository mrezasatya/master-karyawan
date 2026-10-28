<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_password_is_birth_date_ddmmyy_and_must_be_changed(): void
    {
        $employee = Employee::factory()->create(['nik' => '012345', 'tgl_lahir' => '1993-12-04']);
        [$account, $password, , $show] = app(AccountService::class)->createForEmployee($employee);

        $this->assertSame('041293', $password);
        $this->assertFalse($show);
        $this->assertTrue($account->must_change_password);
        $this->assertNotSame('041293', $account->getRawOriginal('password_hash'));

        $this->post('/login', ['username' => '012345', 'password' => '041293'])->assertRedirect(route('portal.index'));
        $this->get('/saya')->assertRedirect(route('password.change'));
        $this->get('/karyawan')->assertRedirect(route('password.change'));
    }

    public function test_new_password_cannot_reuse_birth_date(): void
    {
        $employee = Employee::factory()->create(['tgl_lahir' => '1990-01-31']);
        app(AccountService::class)->createForEmployee($employee);
        $this->post('/login', ['username' => $employee->nik, 'password' => '310190']);

        $this->post('/ganti-password', ['current_password' => '310190', 'password' => 'abc310190', 'password_confirmation' => 'abc310190'])
            ->assertSessionHasErrors('password');
        $this->post('/ganti-password', ['current_password' => '310190', 'password' => 'Lapangan2026', 'password_confirmation' => 'Lapangan2026'])
            ->assertRedirect(route('portal.index'));
        $this->assertFalse($employee->account->fresh()->must_change_password);
    }

    public function test_login_error_does_not_reveal_whether_nik_exists(): void
    {
        $user = $this->employeeUser();
        $message = __('auth.failed');
        $this->post('/login', ['username' => $user->username, 'password' => 'salah'])->assertSessionHasErrors(['username' => $message]);
        $this->post('/login', ['username' => '999999999', 'password' => 'salah'])->assertSessionHasErrors(['username' => $message]);
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = $this->employeeUser();
        $user->update(['account_status' => 'Nonaktif']);

        $this->post('/login', ['username' => $user->username, 'password' => 'Karyawan#2026'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_account_deactivated_mid_session_is_logged_out(): void
    {
        $user = $this->employeeUser();
        $this->actingAs($user)->get('/saya')->assertOk();
        $user->update(['account_status' => 'Nonaktif']);

        $this->actingAs($user->fresh())->get('/saya')->assertRedirect(route('login'));
    }

    public function test_login_is_rate_limited_and_logged(): void
    {
        $user = $this->employeeUser();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => $user->username, 'password' => 'salah'.$i]);
        }
        $this->post('/login', ['username' => $user->username, 'password' => 'Karyawan#2026'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'Login-security-event')->exists());
    }

    public function test_hr_reset_generates_new_initial_credential(): void
    {
        $hr = $this->hr();
        $user = $this->employeeUser(Employee::factory()->create(['tgl_lahir' => '1985-07-09']));

        $this->actingAs($hr)->post(route('accounts.reset', $user))->assertSessionHas('credential');
        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(\Hash::check('090785', $user->password_hash));
    }
}
