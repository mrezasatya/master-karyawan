<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            '_form' => 'profil', 'nik' => '000123', 'nama' => 'Budi Contoh', 'jenis_kelamin' => 'Laki-laki',
            'jobtitle' => 'Supervisor', 'tgl_masuk' => '2022-05-01', 'akhir_kontrak' => '2027-05-01',
            'tgl_lahir' => '1990-02-03', 'no_hp' => '0812-3456-7890',
        ], $override);
    }

    public function test_hr_can_create_search_edit_and_deactivate_employee(): void
    {
        $hr = $this->hr();
        $this->actingAs($hr)->post('/karyawan', $this->payload())->assertRedirect();

        $e = Employee::where('nik', '000123')->firstOrFail();
        $this->assertSame('000123', $e->nik, 'Leading zero NIK harus dipertahankan');
        $this->assertSame('081234567890', $e->no_hp);
        $this->assertSame('Aktif', $e->status_karyawan);

        $this->actingAs($hr)->get('/karyawan?q=budi')->assertSee('Budi Contoh');
        $this->actingAs($hr)->get('/karyawan?q=000123')->assertSee('Budi Contoh');

        $this->actingAs($hr)->put('/karyawan/'.$e->employee_id, ['section' => 'pekerjaan', '_form' => 'pekerjaan', 'jobtitle' => 'Site Manager', 'tgl_masuk' => '2022-05-01'])->assertRedirect();
        $this->assertSame('Site Manager', $e->fresh()->jobtitle);

        $user = $this->employeeUser($e->fresh());
        $this->actingAs($hr)->post('/karyawan/'.$e->employee_id.'/status')->assertRedirect();
        $this->assertSame('Nonaktif', $e->fresh()->status_karyawan);
        $this->assertSame('Nonaktif', $user->fresh()->account_status);
        $this->assertDatabaseHas('employees', ['nik' => '000123']);
    }

    public function test_nik_must_be_unique(): void
    {
        $hr = $this->hr();
        Employee::factory()->create(['nik' => '000123']);
        $this->actingAs($hr)->post('/karyawan', $this->payload())->assertSessionHasErrors('nik', null, 'profil');
        $this->assertSame(1, Employee::where('nik', '000123')->count());
    }

    public function test_cross_field_date_validation(): void
    {
        $hr = $this->hr();
        $this->actingAs($hr)->post('/karyawan', $this->payload(['akhir_kontrak' => '2020-01-01']))->assertSessionHasErrors('akhir_kontrak', null, 'profil');
        $this->actingAs($hr)->post('/karyawan', $this->payload(['tgl_lahir' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('tgl_lahir', null, 'profil');
        $this->actingAs($hr)->post('/karyawan', $this->payload(['email' => 'bukan-email']))->assertSessionHasErrors('email', null, 'profil');
        $this->assertSame(0, Employee::count());
    }

    public function test_employee_can_have_multiple_active_projects_but_no_accidental_duplicates(): void
    {
        $hr = $this->hr();
        $e = Employee::factory()->create();
        [$p1, $p2] = [Project::create(['project_name' => 'A', 'status' => 'Aktif']), Project::create(['project_name' => 'B', 'status' => 'Aktif'])];

        $this->actingAs($hr)->post("/karyawan/{$e->employee_id}/penugasan", ['project_id' => $p1->id, 'assignment_start' => '2026-01-01', 'assignment_status' => 'Aktif']);
        $this->actingAs($hr)->post("/karyawan/{$e->employee_id}/penugasan", ['project_id' => $p2->id, 'assignment_start' => '2026-02-01', 'assignment_status' => 'Aktif']);
        $this->actingAs($hr)->post("/karyawan/{$e->employee_id}/penugasan", ['project_id' => $p1->id, 'assignment_start' => '2026-01-01', 'assignment_status' => 'Aktif'])
            ->assertSessionHasErrors('project_id', null, 'asg-new');

        $this->assertSame(2, $e->activeProjects()->count());
        $this->actingAs($hr)->get('/karyawan?project='.$p2->id)->assertSee($e->nama);
    }

    public function test_project_deactivation_warns_when_active_assignments_exist(): void
    {
        $hr = $this->hr();
        $p = Project::create(['project_name' => 'Tol', 'status' => 'Aktif']);
        Employee::factory()->create()->assignments()->create(['project_id' => $p->id, 'assignment_status' => 'Aktif']);

        $this->actingAs($hr)->put('/proyek/'.$p->id, ['project_name' => 'Tol', 'status' => 'Nonaktif'])->assertSessionHas('warning');
        $this->assertSame('Aktif', $p->fresh()->status);
        $this->actingAs($hr)->put('/proyek/'.$p->id, ['project_name' => 'Tol', 'status' => 'Nonaktif', 'confirm_deactivate' => '1']);
        $this->assertSame('Nonaktif', $p->fresh()->status);
        $this->assertSame(1, $p->assignments()->count(), 'Riwayat penugasan tidak dihapus');
    }

    public function test_photo_upload_is_validated_and_stored_privately(): void
    {
        \Storage::fake('local');
        $hr = $this->hr();
        $e = Employee::factory()->create();

        $this->actingAs($hr)->post("/karyawan/{$e->employee_id}/foto", ['foto' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors('foto', null, 'foto');
        $this->actingAs($hr)->post("/karyawan/{$e->employee_id}/foto", ['foto' => UploadedFile::fake()->image('foto.jpg', 600, 800)])->assertRedirect();

        $path = $e->fresh()->foto_path;
        $this->assertStringStartsWith('photos/', $path);
        \Storage::disk('local')->assertExists($path);
        $this->actingAs($hr)->get("/karyawan/{$e->employee_id}/foto")->assertOk();
    }

    public function test_dashboard_shows_contract_reminders_including_overdue(): void
    {
        $hr = $this->hr();
        $soon = Employee::factory()->create(['nama' => 'Kontrak Segera', 'akhir_kontrak' => now()->addDays(20)]);
        $late = Employee::factory()->create(['nama' => 'Kontrak Lewat', 'akhir_kontrak' => now()->subDays(5)]);
        Employee::factory()->create(['nama' => 'Kontrak Lama', 'akhir_kontrak' => now()->addDays(200)]);
        Employee::factory()->create(['nama' => 'Sudah Nonaktif', 'akhir_kontrak' => now()->subDays(5), 'status_karyawan' => 'Nonaktif']);

        $this->actingAs($hr)->get('/dashboard')->assertOk()
            ->assertSee('Kontrak Segera')->assertSee('Kontrak Lewat')->assertSee('Lewat tanggal kontrak')
            ->assertDontSee('Kontrak Lama')->assertDontSee('Sudah Nonaktif');
        $this->assertSame('Aktif', $late->fresh()->status_karyawan, 'Kontrak lewat tidak menonaktifkan otomatis');
        $this->assertSame('≤ 30 hari', $soon->contractReminder());
    }
}
