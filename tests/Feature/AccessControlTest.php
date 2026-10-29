<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function experienceFor(Employee $e): WorkExperience
    {
        return $e->experiences()->create([
            'experience_type' => 'Sebelum bergabung', 'company_name' => 'PT A', 'position' => 'Welder',
            'start_month' => 1, 'start_year' => 2015, 'end_month' => 2, 'end_year' => 2016,
        ]);
    }

    public function test_employee_cannot_open_hr_pages(): void
    {
        $user = $this->employeeUser();
        $other = Employee::factory()->create();

        foreach (['/dashboard', '/karyawan', '/karyawan/'.$other->employee_id, '/proyek', '/import', '/export',
            '/cv', '/cv/'.$user->employee_id.'/pdf', '/cv/'.$other->employee_id.'/preview', '/akun', '/audit',
            '/karyawan/'.$other->employee_id.'/foto'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
        $this->actingAs($user)->put('/karyawan/'.$user->employee_id, ['section' => 'identitas', 'nama' => 'X'])->assertForbidden();
        $this->actingAs($user)->post('/karyawan/'.$user->employee_id.'/training', ['item_type' => 'Pelatihan', 'item_name' => 'X'])->assertForbidden();
        $this->actingAs($user)->post('/karyawan/'.$user->employee_id.'/pendidikan', ['level' => 'S1', 'institution' => 'X'])->assertForbidden();
    }

    public function test_employee_portal_hides_sensitive_data(): void
    {
        $employee = Employee::factory()->create(['no_ktp' => '3201234567890123', 'nama_ibu' => 'Ibu Rahasia', 'alamat' => 'Jalan Rahasia 9']);
        $this->actingAs($this->employeeUser($employee))->get('/saya')
            ->assertOk()->assertSee($employee->nama)
            ->assertDontSee('3201234567890123')->assertDontSee('Ibu Rahasia')->assertDontSee('Jalan Rahasia 9');
    }

    public function test_employee_can_add_and_edit_own_experience_and_it_is_recorded(): void
    {
        $user = $this->employeeUser();
        $this->actingAs($user)->postJson('/pengalaman', [
            'experience_type' => 'Sebelum bergabung', 'company_name' => 'PT Baja', 'position' => 'Fitter',
            'start_month' => 3, 'start_year' => 2018, 'end_month' => 6, 'end_year' => 2020, 'responsibilities' => 'Fabrikasi',
        ])->assertOk()->assertJsonPath('message', 'Pengalaman kerja tersimpan.');

        $exp = WorkExperience::firstOrFail();
        $this->assertSame($user->employee_id, $exp->employee_id);
        $this->assertSame($user->id, $exp->created_by);

        $this->actingAs($user)->putJson('/pengalaman/'.$exp->id, [...$exp->only(['experience_type', 'company_name', 'start_month', 'start_year', 'end_month', 'end_year']), 'position' => 'Leader Fitter'])
            ->assertOk();
        $this->assertSame('Leader Fitter', $exp->fresh()->position);
        $this->assertSame($user->id, $exp->fresh()->updated_by);
        $this->assertTrue(AuditLog::where('entity_type', 'work_experience')->where('action', 'Update')->where('actor_user_id', $user->id)->exists());
    }

    public function test_employee_cannot_change_other_employees_experience_by_changing_id(): void
    {
        $user = $this->employeeUser();
        $other = Employee::factory()->create();
        $exp = $this->experienceFor($other);

        $this->actingAs($user)->putJson('/pengalaman/'.$exp->id, ['experience_type' => 'Sebelum bergabung', 'company_name' => 'Hack', 'position' => 'X', 'start_month' => 1, 'start_year' => 2015, 'end_month' => 1, 'end_year' => 2016])
            ->assertForbidden();
        $this->actingAs($user)->deleteJson('/pengalaman/'.$exp->id)->assertForbidden();
        $this->assertSame('PT A', $exp->fresh()->company_name);
        $this->assertNull($exp->fresh()->deleted_at);

        // Menyisipkan employee_id orang lain saat membuat juga ditolak.
        $this->actingAs($user)->postJson('/pengalaman', ['employee_id' => $other->employee_id, 'experience_type' => 'Sebelum bergabung', 'company_name' => 'X', 'position' => 'X', 'start_month' => 1, 'start_year' => 2015, 'end_month' => 1, 'end_year' => 2016])
            ->assertForbidden();
        $this->assertSame(1, $other->experiences()->count());

        $this->assertGreaterThanOrEqual(3, AuditLog::where('action', 'Login-security-event')->count());
    }

    public function test_hr_can_manage_any_employee_experience(): void
    {
        $hr = $this->hr();
        $employee = Employee::factory()->create();
        $exp = $this->experienceFor($employee);

        $this->actingAs($hr)->put('/pengalaman/'.$exp->id, ['experience_type' => 'Selama bekerja', 'company_name' => 'PT Bukaka', 'position' => 'SPV', 'start_month' => 1, 'start_year' => 2015, 'is_current' => '1'])
            ->assertRedirect();
        $this->assertTrue($exp->fresh()->is_current);
        $this->assertNull($exp->fresh()->end_year);
    }
}
