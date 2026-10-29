<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Data fiktif untuk development/testing. Jangan gunakan data karyawan asli. */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['Laki-laki', 'Perempuan']);
        $masuk = $this->faker->dateTimeBetween('-8 years', '-2 months');

        return [
            'nik' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'nama' => $this->faker->name($gender === 'Laki-laki' ? 'male' : 'female'),
            'jenis_kelamin' => $gender,
            'kode_jbt' => $this->faker->randomElement(['Staf', 'Section Head', 'Supervisor', 'Department Head']),
            'jobtitle' => $this->faker->randomElement(['Site Engineer', 'HSE Officer', 'QC Inspector', 'Supervisor Site', 'Welder', 'Surveyor', 'Project Admin', 'Mechanical Engineer']),
            'kategori_karyawan' => 'Karyawan Proyek',
            'tgl_masuk' => $masuk,
            'kontrak_ke' => (string) $this->faker->numberBetween(1, 4),
            'akhir_kontrak' => $this->faker->dateTimeBetween('-20 days', '+14 months'),
            'status_karyawan' => 'Aktif',
            'tgl_lahir' => $this->faker->dateTimeBetween('-55 years', '-20 years'),
            'tempat_lahir' => $this->faker->city(),
            'kewarganegaraan' => 'Indonesian',
            'alamat' => $this->faker->address(),
            'agama' => $this->faker->randomElement(config('karyawan.options.agama')),
            'status_nikah' => $this->faker->randomElement(['Belum Menikah', 'Menikah']),
            'status_ptkp' => $this->faker->randomElement(['TK0', 'K0', 'K1', 'K2']),
            'no_hp' => '08'.$this->faker->numerify('##########'),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
