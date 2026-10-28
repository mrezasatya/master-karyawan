<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\Project;
use App\Services\Excel\EmployeeExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    private const LEGACY_HEADERS = ['NIK', 'Nama', 'NamaPanggilan', 'JenisKelamin', 'KodeJbt', 'TglMasuk', 'Status', 'StatusAwal', 'KontrakKe', 'AkhirKontrak', 'TglLahir', 'TempatLahir', 'Pendidikan', 'Jurusan', 'Sekolah', 'Alamat', 'GolDarah', 'TinggiBadan', 'BeratBadan', 'Agama', 'NoKTP', 'No BPJS TK', 'StatusNikah', 'Jobtitle', 'NPWP', 'No BPJS Kes', 'KK', 'NamaIbu', 'Status PTKP', 'NoHP', 'email', 'Project'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Membuat file seperti master Excel lama: satu sheet, NIK & tanggal bertipe angka. */
    private function legacyFile(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('masterKaryawan edited');
        $sheet->fromArray(self::LEGACY_HEADERS, null, 'A1');
        foreach ($rows as $i => $row) {
            $values = [];
            foreach (self::LEGACY_HEADERS as $h) {
                $v = $row[$h] ?? null;
                if (in_array($h, ['TglMasuk', 'AkhirKontrak', 'TglLahir'], true) && $v && ! str_contains($v, '/')) {
                    $v = ExcelDate::PHPToExcel(new \DateTime($v));
                }
                $values[] = $v;
            }
            $sheet->fromArray($values, null, 'A'.($i + 2), true);
            // Excel menyimpan nomor panjang sebagai angka (seperti file master lama).
            if (is_int($row['NoKTP'] ?? null)) {
                $sheet->setCellValueExplicit('U'.($i + 2), $row['NoKTP'], DataType::TYPE_NUMERIC);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'master.xlsx', null, null, true);
    }

    private function row(array $o = []): array
    {
        return array_merge([
            'NIK' => 99999, 'Nama' => 'Andi Uji', 'JenisKelamin' => 'Laki-laki', 'KodeJbt' => 'Section Head',
            'TglMasuk' => '2021-01-26', 'Status' => 'Karyawan Proyek', 'KontrakKe' => 1, 'AkhirKontrak' => '2027-01-25',
            'TglLahir' => '1997-08-20', 'TempatLahir' => 'Wonogiri', 'Pendidikan' => 'D3', 'Jurusan' => 'Teknik Otomasi',
            'Sekolah' => 'Politeknik Manufaktur', 'Agama' => 'Katholik', 'NoKTP' => 1111111111111110, 'StatusNikah' => 'Menikah',
            'Jobtitle' => 'Supervisi Site', 'Status PTKP' => 'K1', 'NoHP' => '0812345678', 'email' => 'andi@example.com', 'Project' => 'Project OKI',
        ], $o);
    }

    private function upload(UploadedFile $file): ImportBatch
    {
        $this->post('/import', ['file' => $file])->assertRedirect();

        return ImportBatch::latest('id')->firstOrFail();
    }

    public function test_preview_does_not_save_and_shows_problems(): void
    {
        $this->actingAs($this->hr());
        $batch = $this->upload($this->legacyFile([
            $this->row(),
            $this->row(['NIK' => 88888, 'Nama' => 'Dua', 'Agama' => 'Kepercayaan']),
            $this->row(['NIK' => 77777, 'Nama' => 'Dup A']),
            $this->row(['NIK' => 77777, 'Nama' => 'Dup B']),
            $this->row(['NIK' => 66666, 'Nama' => '', 'JenisKelamin' => 'X']),
            $this->row(['NIK' => 55555, 'AkhirKontrak' => '2019-01-01']),
        ]));

        $this->get(route('imports.show', $batch))->assertOk()
            ->assertSee('NIK muncul lebih dari satu kali')
            ->assertSee('Kepercayaan')
            ->assertSee('Akhir kontrak tidak boleh sebelum tanggal masuk')
            ->assertSee('Project OKI')
            ->assertSee('kehilangan digit terakhir')
            ->assertSee('NIK tersimpan sebagai angka');
        $this->assertSame(0, Employee::count(), 'Preview tidak boleh menyimpan data');
    }

    public function test_commit_saves_valid_rows_and_reports_invalid_ones(): void
    {
        $this->actingAs($this->hr());
        $batch = $this->upload($this->legacyFile([
            $this->row(),
            $this->row(['NIK' => 77777, 'Nama' => 'Dup A']),
            $this->row(['NIK' => 77777, 'Nama' => 'Dup B']),
            $this->row(['NIK' => 66666, 'JenisKelamin' => 'X']),
            $this->row(['NIK' => '012345', 'Nama' => 'Leading Zero', 'Agama' => 'Kepercayaan', 'TglMasuk' => '15/03/2020', 'Project' => 'Project OKI']),
        ]));

        $this->post(route('imports.process', $batch), ['confirm' => '1', 'create_projects' => '1', 'create_accounts' => '1'])->assertRedirect();
        $batch->refresh();

        $this->assertSame('Selesai', $batch->status);
        $this->assertSame(2, $batch->created_count);
        $this->assertSame(3, $batch->failed_count);
        $this->assertNull($batch->stored_path);

        $andi = Employee::where('nik', '99999')->firstOrFail();
        $this->assertSame('Katolik', $andi->agama);
        $this->assertSame('Karyawan Proyek', $andi->kategori_karyawan);
        $this->assertSame('2027-01-25', $andi->akhir_kontrak->format('Y-m-d'));
        $this->assertSame('1111111111111110', $andi->no_ktp);
        $this->assertSame('D3', $andi->education()->first()->level);
        $this->assertSame(['Project OKI'], $andi->activeProjects()->pluck('project_name')->all());
        $this->assertNotNull($andi->account, 'Akun dibuat untuk profil baru');

        $zero = Employee::where('nik', '012345')->firstOrFail();
        $this->assertNull($zero->agama, 'Nilai tidak dikenali tidak diimpor');
        $this->assertSame('2020-03-15', $zero->tgl_masuk->format('Y-m-d'));
        $this->assertSame(1, Project::count(), 'Proyek dibuat sekali walau dipakai beberapa baris');
        $this->assertFalse(Employee::where('nik', '77777')->exists());

        $this->assertNotEmpty($batch->error_summary['rows']);
        $this->assertArrayHasKey('agama', $batch->error_summary['mapping']);
        $this->get(route('imports.report', $batch))->assertOk();
    }

    public function test_reimport_updates_by_nik_without_deleting_or_blanking_data(): void
    {
        $this->actingAs($this->hr());
        $keep = Employee::factory()->create(['nik' => '50000', 'nama' => 'Tidak Ada Di File']);
        $existing = Employee::factory()->create(['nik' => '99999', 'nama' => 'Nama Lama', 'email' => 'lama@example.com', 'alamat' => 'Alamat lama', 'jobtitle' => 'Welder']);

        $batch = $this->upload($this->legacyFile([$this->row(['email' => null, 'Alamat' => null, 'Project' => null])]));
        $this->get(route('imports.show', $batch))->assertSee('Nama Lama')->assertSee('Andi Uji');
        $this->post(route('imports.process', $batch), ['confirm' => '1']);

        $existing->refresh();
        $this->assertSame('Andi Uji', $existing->nama);
        $this->assertSame('Supervisi Site', $existing->jobtitle);
        $this->assertSame('lama@example.com', $existing->email, 'Sel kosong tidak menghapus data lama');
        $this->assertSame('Alamat lama', $existing->alamat);
        $this->assertDatabaseHas('employees', ['employee_id' => $keep->employee_id, 'nama' => 'Tidak Ada Di File']);
        $this->assertSame(1, ImportBatch::latest('id')->first()->updated_count);
    }

    public function test_wrong_length_kk_is_warning_not_row_failure(): void
    {
        $this->actingAs($this->hr());
        $batch = $this->upload($this->legacyFile([$this->row(['KK' => 22222222222])]));
        $this->get(route('imports.show', $batch))->assertSee('11 digit (seharusnya 16)');
        $this->post(route('imports.process', $batch), ['confirm' => '1']);

        $e = Employee::where('nik', '99999')->firstOrFail();
        $this->assertNull($e->kk);
    }

    public function test_unknown_project_is_skipped_without_confirmation(): void
    {
        $this->actingAs($this->hr());
        $batch = $this->upload($this->legacyFile([$this->row(['Project' => 'Proyek Baru X'])]));
        $this->post(route('imports.process', $batch), ['confirm' => '1']);

        $this->assertSame(0, Project::count());
        $this->assertNotEmpty($batch->fresh()->error_summary['assignments']);
    }

    public function test_missing_required_columns_is_rejected(): void
    {
        $this->actingAs($this->hr());
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['NIK', 'Nama'], ['1', 'A']]);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($book))->save($path);

        $this->post('/import', ['file' => new UploadedFile($path, 'x.xlsx', null, null, true)])
            ->assertRedirect(route('imports.index'))->assertSessionHas('error');
        $this->assertSame(0, Employee::count());
    }

    public function test_export_then_reimport_round_trip_is_idempotent(): void
    {
        $this->actingAs($this->hr());
        $p = Project::create(['project_code' => 'P1', 'project_name' => 'Proyek Satu', 'status' => 'Aktif']);
        $e = Employee::factory()->create(['nik' => '000777']);
        $e->assignments()->create(['project_id' => $p->id, 'assignment_start' => '2025-01-01', 'assignment_status' => 'Aktif']);
        $e->education()->create(['level' => 'S1', 'major' => 'Sipil', 'institution' => 'UNDIP', 'graduation_year' => 2015]);
        $e->languages()->create(['language_name' => 'Bahasa Indonesia']);

        $this->get('/export')->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'exp').'.xlsx';
        $exporter = app(EmployeeExporter::class);
        $exporter->save($exporter->build(Employee::query()), $path);

        $batch = $this->upload(new UploadedFile($path, 'export.xlsx', null, null, true));
        $this->post(route('imports.process', $batch), ['confirm' => '1']);

        $batch->refresh();
        $this->assertSame(0, $batch->failed_count, json_encode($batch->error_summary));
        $this->assertSame(1, Employee::count());
        $this->assertSame('000777', Employee::first()->nik);
        $this->assertSame(1, $e->assignments()->count(), 'Penugasan tidak terduplikasi');
        $this->assertSame(1, $e->education()->count());
        $this->assertSame(1, $e->languages()->count());
    }
}
