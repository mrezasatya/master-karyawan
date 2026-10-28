<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\CvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvTest extends TestCase
{
    use RefreshDatabase;

    private function completeEmployee(): Employee
    {
        $e = Employee::factory()->create([
            'nama' => 'Bagus Contoh', 'jenis_kelamin' => 'Laki-laki', 'tgl_lahir' => '1993-12-04', 'kewarganegaraan' => 'Indonesian',
            'jobtitle' => 'Supervisor', 'no_hp' => '082314906247', 'email' => null,
            'no_ktp' => '3201999988887777', 'npwp' => '01.234.567.8-999.000', 'no_bpjs_tk' => 'BPJSTK123', 'no_bpjs_kes' => 'BPJSKES456',
            'kk' => '3201000011112222', 'nama_ibu' => 'Siti Ibunda', 'agama' => 'Islam', 'gol_darah' => 'AB', 'alamat' => 'Jl. Rahasia No. 1',
            'tinggi_badan_cm' => 171, 'berat_badan_kg' => 68.5, 'status_nikah' => 'Menikah', 'status_ptkp' => 'K1', 'tempat_lahir' => 'Kotarahasia',
        ]);
        $e->education()->create(['level' => 'D3', 'major' => 'Civil Engineering', 'institution' => 'Universitas Diponegoro', 'graduation_year' => 2016]);
        $e->education()->create(['level' => 'SMA/SMK', 'institution' => 'SMA 1', 'graduation_year' => 2012]);
        $e->languages()->create(['language_name' => 'Bahasa Indonesia']);
        $e->experiences()->create(['experience_type' => 'Selama bekerja', 'company_name' => 'PT Bukaka', 'project_name' => 'Project Callender Hamilton', 'position' => 'Supervisor', 'start_month' => 5, 'start_year' => 2022, 'end_month' => 4, 'end_year' => 2024]);
        $e->experiences()->create(['experience_type' => 'Selama bekerja', 'company_name' => 'PT Bukaka', 'project_name' => 'Project CSP IKN', 'position' => 'Supervisor', 'start_month' => 4, 'start_year' => 2024, 'end_month' => 11, 'end_year' => 2025]);
        $e->trainings()->create(['item_type' => 'Sertifikasi', 'item_name' => 'ISO 9001', 'year' => 2026, 'organizer' => 'BLI - Lembaga', 'certificate_number' => 'CERT-SECRET-1']);

        return $e;
    }

    public function test_cv_maps_template_fields_and_never_prints_sensitive_data(): void
    {
        $e = $this->completeEmployee();
        $html = $this->actingAs($this->hr())->get(route('cv.preview', $e))->assertOk()->getContent();

        foreach (['CURRICULUM VITAE OF PERSONNEL', 'Bagus Contoh', 'Male', 'Dec 04, 1993', 'Indonesian', 'Supervisor', '082314906247', 'Bahasa Indonesia',
            '2016 - Diploma Graduate - Civil Engineering - Universitas Diponegoro', 'Supervisor di Project CSP IKN', 'ISO 9001', 'BLI - Lembaga', 'TAHUN', 'PENYELENGGARA'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertMatchesRegularExpression('#Mei 22</td>\s*<td class="sd">s/d</td>\s*<td class="end">Apr 24</td>#', $html);

        // Bandingkan dengan teks yang terlihat (tanpa CSS dan gambar base64).
        $text = strip_tags(preg_replace('#<style.*?</style>#s', '', $html));
        foreach (['3201999988887777', '01.234.567.8-999.000', 'BPJSTK123', 'BPJSKES456', '3201000011112222', 'Siti Ibunda', 'Islam', 'Jl. Rahasia', 'Menikah', 'K1', '171', '68.5', 'Kotarahasia', 'CERT-SECRET-1', 'Sertifikasi'] as $secret) {
            $this->assertStringNotContainsString($secret, $text, "Data sensitif '{$secret}' tidak boleh tampil di CV");
        }

        // Pendidikan & pengalaman terbaru lebih dulu.
        $this->assertLessThan(strpos($html, 'SMA 1'), strpos($html, 'Universitas Diponegoro'));
        $this->assertLessThan(strpos($html, 'Callender Hamilton'), strpos($html, 'CSP IKN'));
    }

    public function test_hr_can_download_pdf_when_required_data_complete(): void
    {
        $e = $this->completeEmployee();
        $response = $this->actingAs($this->hr())->get(route('cv.pdf', $e))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertDatabaseHas('audit_logs', ['entity_id' => $e->employee_id, 'action' => 'GenerateCV']);
    }

    public function test_long_cv_continues_on_next_page(): void
    {
        $e = $this->completeEmployee();
        for ($i = 0; $i < 25; $i++) {
            $e->experiences()->create(['experience_type' => 'Sebelum bergabung', 'company_name' => 'PT '.$i, 'position' => 'Engineer', 'start_month' => 1, 'start_year' => 1995 + $i % 25, 'end_month' => 2, 'end_year' => 1996 + $i % 25, 'responsibilities' => str_repeat('Uraian tanggung jawab yang panjang. ', 12)]);
        }
        $pdf = app(CvService::class)->pdf($e);
        preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $m);
        $this->assertGreaterThan(1, count($m[0]));
    }

    public function test_download_blocked_when_required_data_missing_and_values_not_invented(): void
    {
        $e = $this->completeEmployee();
        $e->update(['kewarganegaraan' => null, 'no_hp' => null, 'email' => null]);
        $hr = $this->hr();

        $this->actingAs($hr)->get(route('cv.pdf', $e))->assertRedirect(route('cv.show', $e));
        $this->actingAs($hr)->get(route('cv.show', $e))->assertSee('Citizen (kewarganegaraan)')->assertSee('Unduhan diblokir');
        $html = $this->actingAs($hr)->get(route('cv.preview', $e))->getContent();
        $this->assertMatchesRegularExpression('#Citizen</td>\s*<td class="colon">:</td>\s*<td></td>#', $html, 'Kewarganegaraan kosong tidak boleh diisi otomatis');
    }
}
