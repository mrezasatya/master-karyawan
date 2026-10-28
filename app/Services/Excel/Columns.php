<?php

namespace App\Services\Excel;

/**
 * Pemetaan kolom Excel. Urutan mengikuti file master lama (32 kolom) lalu kolom baru,
 * sehingga file hasil export dapat di-import kembali.
 */
class Columns
{
    /** Header Excel => field internal. Field "edu.*" untuk riwayat pendidikan. */
    public const EMPLOYEE = [
        'NIK' => 'nik',
        'Nama' => 'nama',
        'NamaPanggilan' => 'nama_panggilan',
        'JenisKelamin' => 'jenis_kelamin',
        'KodeJbt' => 'kode_jbt',
        'TglMasuk' => 'tgl_masuk',
        'Status' => 'kategori_karyawan',
        'StatusAwal' => 'status_awal',
        'KontrakKe' => 'kontrak_ke',
        'AkhirKontrak' => 'akhir_kontrak',
        'TglLahir' => 'tgl_lahir',
        'TempatLahir' => 'tempat_lahir',
        'Pendidikan' => 'edu.level',
        'Jurusan' => 'edu.major',
        'Sekolah' => 'edu.institution',
        'Alamat' => 'alamat',
        'GolDarah' => 'gol_darah',
        'TinggiBadan' => 'tinggi_badan_cm',
        'BeratBadan' => 'berat_badan_kg',
        'Agama' => 'agama',
        'NoKTP' => 'no_ktp',
        'No BPJS TK' => 'no_bpjs_tk',
        'StatusNikah' => 'status_nikah',
        'Jobtitle' => 'jobtitle',
        'NPWP' => 'npwp',
        'No BPJS Kes' => 'no_bpjs_kes',
        'KK' => 'kk',
        'NamaIbu' => 'nama_ibu',
        'Status PTKP' => 'status_ptkp',
        'NoHP' => 'no_hp',
        'email' => 'email',
        'Project' => 'project',
        // Kolom baru
        'Kewarganegaraan' => 'kewarganegaraan',
        'StatusKaryawan' => 'status_karyawan',
        'TahunLulus' => 'edu.graduation_year',
        'Bahasa' => 'languages',
    ];

    public const ASSIGNMENT = [
        'NIK' => 'nik',
        'KodeProyek' => 'project_code',
        'NamaProyek' => 'project_name',
        'TglMulai' => 'assignment_start',
        'TglSelesai' => 'assignment_end',
        'StatusPenugasan' => 'assignment_status',
    ];

    public const REQUIRED = ['nik', 'nama', 'jenis_kelamin', 'jobtitle', 'tgl_masuk'];

    public const DATE_FIELDS = ['tgl_masuk', 'akhir_kontrak', 'tgl_lahir', 'assignment_start', 'assignment_end'];

    /** Kolom nomor panjang yang wajib berformat Text di Excel. */
    public const LONG_NUMBER_FIELDS = ['no_ktp', 'kk', 'npwp', 'no_bpjs_tk', 'no_bpjs_kes'];

    public const ENUM_FIELDS = ['jenis_kelamin', 'agama', 'status_nikah', 'gol_darah', 'status_ptkp', 'status_karyawan'];

    /** Field yang perubahannya ditampilkan nilai lama → baru di preview (bukan data sensitif). */
    public const IMPORTANT = ['nama', 'jobtitle', 'kode_jbt', 'tgl_masuk', 'kontrak_ke', 'akhir_kontrak', 'status_karyawan', 'kategori_karyawan', 'kewarganegaraan'];

    public static function normalizeHeader(?string $header): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $header));
    }

    /** @return array<string,string> header ternormalisasi => field */
    public static function lookup(array $map): array
    {
        $out = [];
        foreach ($map as $header => $field) {
            $out[self::normalizeHeader($header)] = $field;
        }

        return $out;
    }
}
