<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Project;
use App\Models\UserAccount;
use App\Services\AccountService;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data DUMMY untuk development/staging. Semua nama, NIK, dan nomor adalah fiktif.
 */
class DemoSeeder extends Seeder
{
    public function run(AccountService $accounts): void
    {
        $faker = Factory::create('id_ID');
        $faker->seed(2026);

        $projects = collect([
            ['PRJ-OKI', 'Project OKI'], ['PRJ-GRT', 'SUTET 500KV Grati - Pasuruan'], ['PRJ-HGR', 'Project Hanggar'],
            ['PRJ-IKN', 'Project CSP IKN'], ['PRJ-CH', 'Project Callender Hamilton'], ['PRJ-JTT', 'Jembatan Tol Trans Jawa'],
        ])->map(fn ($p) => Project::create(['project_code' => $p[0], 'project_name' => $p[1], 'status' => 'Aktif']));
        Project::create(['project_code' => 'PRJ-OLD', 'project_name' => 'Gedung Workshop Cileungsi (selesai)', 'status' => 'Nonaktif']);

        $trainings = [
            ['Sertifikasi', 'Ahli K3 Umum', 'Kemnaker RI'], ['Pelatihan', 'ISO 9001:2015 Awareness', 'BLI - Lembaga Pelatihan'],
            ['Kompetensi', 'Juru Las 3G SMAW', 'LSP Logam'], ['Pelatihan', 'Project Management Fundamental', 'PT Pelatihan Nusantara'],
            ['Sertifikasi', 'Non-Destructive Test Level II', 'PT Inspeksi Teknik'], ['Pelatihan', 'People Development', 'BLI - Lembaga Pelatihan'],
        ];
        $levels = [['S1', 'Teknik Sipil', 'Universitas Diponegoro'], ['D3', 'Teknik Mesin', 'Politeknik Negeri Bandung'], ['SMA/SMK', 'Teknik Las', 'SMKN 1 Cibinong'], ['S1', 'Teknik Elektro', 'Institut Teknologi Sepuluh Nopember'], ['S2', 'Manajemen Proyek', 'Universitas Indonesia']];

        Employee::factory()->count(60)->make()->each(function (Employee $e, $i) use ($faker, $projects, $trainings, $levels) {
            $e->nik = str_pad((string) (1000 + $i), 6, '0', STR_PAD_LEFT); // leading zero disengaja
            if ($i % 17 === 0) {
                $e->status_karyawan = 'Nonaktif';
            }
            if ($i % 11 === 0) {
                $e->kewarganegaraan = null; // contoh data belum lengkap untuk CV
            }
            $e->save();

            [$level, $major, $school] = $levels[$i % count($levels)];
            $e->education()->create(['level' => $level, 'major' => $major, 'institution' => $school, 'graduation_year' => $i % 9 === 0 ? null : $faker->numberBetween(2000, 2020)]);
            $e->languages()->create(['language_name' => 'Bahasa Indonesia']);
            if ($i % 3 === 0) {
                $e->languages()->create(['language_name' => 'Bahasa Inggris']);
            }

            $e->assignments()->create(['project_id' => $projects[$i % 6]->id, 'assignment_start' => $e->tgl_masuk, 'assignment_status' => 'Aktif']);
            if ($i % 5 === 0) {
                $e->assignments()->create(['project_id' => $projects[($i + 2) % 6]->id, 'assignment_start' => now()->subMonths(3), 'assignment_status' => 'Aktif']);
            }

            foreach ($faker->randomElements($trainings, $faker->numberBetween(0, 4)) as $t) {
                $e->trainings()->create(['item_type' => $t[0], 'item_name' => $t[1], 'organizer' => $t[2], 'year' => $faker->numberBetween(2018, 2026)]);
            }

            $startYear = $faker->numberBetween(2012, 2018);
            $e->experiences()->create([
                'experience_type' => 'Sebelum bergabung', 'company_name' => 'PT '.$faker->lastName().' Konstruksi',
                'position' => $e->jobtitle, 'start_month' => $faker->numberBetween(1, 12), 'start_year' => $startYear,
                'end_month' => $faker->numberBetween(1, 12), 'end_year' => $startYear + 2,
                'responsibilities' => 'Melaksanakan pekerjaan '.Str::lower($e->jobtitle).' sesuai gambar kerja dan standar K3.',
            ]);
            $e->experiences()->create([
                'experience_type' => 'Selama bekerja', 'company_name' => 'PT Bukaka Teknik Utama',
                'project_name' => $projects[$i % 6]->project_name, 'position' => $e->jobtitle,
                'start_month' => (int) $e->tgl_masuk->format('n'), 'start_year' => (int) $e->tgl_masuk->format('Y'), 'is_current' => true,
                'responsibilities' => 'Bertanggung jawab atas pelaksanaan pekerjaan di lapangan, pelaporan harian, dan koordinasi dengan tim proyek.',
            ]);
        });

        // Akun HR demo — password dari env DEMO_HR_PASSWORD atau dibuat acak dan ditampilkan sekali.
        $hrPassword = env('DEMO_HR_PASSWORD') ?: $accounts->randomPassword().'!9';
        UserAccount::create([
            'username' => 'HR0001', 'display_name' => 'HR Pusat (Demo)', 'role' => 'HR',
            'password_hash' => $hrPassword, 'must_change_password' => false, 'account_status' => 'Aktif',
        ]);

        // Akun karyawan demo: password awal = tanggal lahir (DDMMYY), wajib ganti saat login pertama.
        Employee::active()->orderBy('nik')->limit(10)->get()->each(fn ($e) => $accounts->createForEmployee($e));
        $first = Employee::active()->orderBy('nik')->first();

        $this->command?->info('Data demo dibuat: '.Employee::count().' karyawan fiktif, '.Project::count().' proyek.');
        $this->command?->info("Login HR demo   : HR0001 / {$hrPassword}");
        $this->command?->info("Login karyawan  : {$first->nik} / {$first->tgl_lahir->format('dmy')} (tanggal lahir DDMMYY)");
    }
}
