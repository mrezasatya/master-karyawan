<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Aturan validasi profil karyawan yang dipakai bersama oleh form HR dan import Excel.
 */
class EmployeeRules
{
    public static function rules(?int $ignoreId = null): array
    {
        $opt = fn (string $key) => Rule::in(config("karyawan.options.$key"));
        $digitsWhenNumeric = fn (string $label) => function ($attribute, $value, $fail) use ($label) {
            if (is_string($value) && ctype_digit($value) && strlen($value) !== 16) {
                $fail("{$label} berupa angka harus 16 digit (saat ini ".strlen($value).' digit).');
            }
        };

        return [
            'nik' => ['required', 'string', 'max:30', Rule::unique('employees', 'nik')->ignore($ignoreId, 'employee_id')],
            'nama' => ['required', 'string', 'max:150'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'jenis_kelamin' => ['required', $opt('jenis_kelamin')],
            'kode_jbt' => ['nullable', 'string', 'max:50'],
            'jobtitle' => ['required', 'string', 'max:150'],
            'kategori_karyawan' => ['nullable', 'string', 'max:100'],
            'status_awal' => ['nullable', 'string', 'max:100'],
            'tgl_masuk' => ['required', 'date'],
            'kontrak_ke' => ['nullable', 'string', 'max:30'],
            'akhir_kontrak' => ['nullable', 'date', 'after_or_equal:tgl_masuk'],
            'status_karyawan' => ['required', $opt('status_karyawan')],
            'tgl_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'kewarganegaraan' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'gol_darah' => ['nullable', $opt('gol_darah')],
            'tinggi_badan_cm' => ['nullable', 'integer', 'min:1', 'max:400'],
            'berat_badan_kg' => ['nullable', 'numeric', 'min:1', 'max:999'],
            'agama' => ['nullable', $opt('agama')],
            'no_ktp' => ['nullable', 'string', 'max:32', $digitsWhenNumeric('No. KTP')],
            'no_bpjs_tk' => ['nullable', 'string', 'max:40'],
            'status_nikah' => ['nullable', $opt('status_nikah')],
            'npwp' => ['nullable', 'string', 'max:32'],
            'no_bpjs_kes' => ['nullable', 'string', 'max:40'],
            'kk' => ['nullable', 'string', 'max:32', $digitsWhenNumeric('No. KK')],
            'nama_ibu' => ['nullable', 'string', 'max:150'],
            'status_ptkp' => ['nullable', $opt('status_ptkp')],
            'no_hp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9]{8,16}$/'],
            'email' => ['nullable', 'email', 'max:254'],
        ];
    }

    public static function messages(): array
    {
        return [
            'nik.unique' => 'NIK sudah terdaftar. Buka profil yang sudah ada untuk mengubah datanya.',
            'no_hp.regex' => 'No. HP harus 8–16 digit angka (boleh diawali +).',
            'akhir_kontrak.after_or_equal' => 'Akhir kontrak tidak boleh sebelum tanggal masuk.',
            'tgl_lahir.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
        ];
    }

    /** Normalisasi nilai sebelum validasi (trim, nomor HP, PTKP, dsb). */
    public static function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim(preg_replace('/\s+/u', ' ', $value));
                $data[$key] = $value === '' ? null : $value;
            }
        }
        if (! empty($data['no_hp'])) {
            $plus = str_starts_with($data['no_hp'], '+') ? '+' : '';
            $data['no_hp'] = $plus.preg_replace('/\D/', '', $data['no_hp']);
        }
        if (! empty($data['status_ptkp'])) {
            $data['status_ptkp'] = strtoupper(str_replace(['/', ' ', '-'], '', $data['status_ptkp']));
        }
        if (! empty($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        return $data;
    }

    /** Peringatan yang tidak menolak data (pemeriksaan kewajaran). */
    public static function warnings(array $data): array
    {
        $warnings = [];
        if (! empty($data['tgl_masuk']) && Carbon::parse($data['tgl_masuk'])->isFuture()) {
            $warnings[] = 'Tanggal masuk berada di masa depan — mohon periksa.';
        }
        if (! empty($data['tinggi_badan_cm']) && ($data['tinggi_badan_cm'] < 50 || $data['tinggi_badan_cm'] > 250)) {
            $warnings[] = 'Tinggi badan di luar rentang wajar 50–250 cm.';
        }
        if (! empty($data['berat_badan_kg']) && ($data['berat_badan_kg'] < 20 || $data['berat_badan_kg'] > 300)) {
            $warnings[] = 'Berat badan di luar rentang wajar 20–300 kg.';
        }
        if (! empty($data['akhir_kontrak']) && Carbon::parse($data['akhir_kontrak'])->isPast() && ($data['status_karyawan'] ?? 'Aktif') === 'Aktif') {
            $warnings[] = 'Akhir kontrak sudah lewat (status karyawan tidak diubah otomatis).';
        }

        return $warnings;
    }
}
