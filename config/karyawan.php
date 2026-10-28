<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password awal akun karyawan
    |--------------------------------------------------------------------------
    |
    | "tanggal_lahir" : password awal = tanggal lahir format DDMMYY (keputusan bisnis).
    | "acak"          : password sementara acak, ditampilkan satu kali kepada HR.
    |
    | Pada kedua mode, password wajib diganti saat login pertama. Akun HR selalu
    | memakai password acak. Jika tanggal lahir kosong, sistem memakai password acak.
    |
    */
    'password_awal' => env('KARYAWAN_PASSWORD_AWAL', 'tanggal_lahir'),

    'force_https' => (bool) env('FORCE_HTTPS', false),

    // Password akun HR demo pada seeder (hanya development). Kosong = dibuat acak.
    'demo_hr_password' => env('DEMO_HR_PASSWORD'),

    // Batas waktu tidak aktif (menit) sebelum sesi berakhir otomatis.
    'idle_timeout' => (int) env('SESSION_LIFETIME', 30),

    // Ambang pengingat akhir kontrak (hari).
    'contract_thresholds' => [30, 60, 90],

    // Foto profil.
    'photo_max_kb' => 2048,
    'photo_max_px' => 800,

    // Jumlah file backup yang dipertahankan.
    'backup_keep' => (int) env('KARYAWAN_BACKUP_KEEP', 14),

    /*
    |--------------------------------------------------------------------------
    | Pilihan terkontrol (enumerasi)
    |--------------------------------------------------------------------------
    */
    'options' => [
        'jenis_kelamin' => ['Laki-laki', 'Perempuan'],
        'status_karyawan' => ['Aktif', 'Nonaktif'],
        'gol_darah' => ['A', 'B', 'AB', 'O', 'Tidak diketahui'],
        'agama' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'],
        'status_nikah' => ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'],
        'status_ptkp' => ['TK0', 'TK1', 'TK2', 'TK3', 'K0', 'K1', 'K2', 'K3', 'KI0', 'KI1', 'KI2', 'KI3'],
        'education_level' => ['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Lainnya'],
        'languages' => ['Bahasa Indonesia', 'Bahasa Inggris'],
        'experience_type' => ['Sebelum bergabung', 'Selama bekerja'],
        'training_type' => ['Pelatihan', 'Sertifikasi', 'Kompetensi'],
        'project_status' => ['Aktif', 'Nonaktif'],
        'assignment_status' => ['Aktif', 'Selesai'],
        'role' => ['HR', 'Karyawan'],
        'account_status' => ['Aktif', 'Nonaktif'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemetaan nilai lama (Excel) ke nilai terstandar
    |--------------------------------------------------------------------------
    |
    | Kunci ditulis huruf kecil. Nilai yang tidak dikenali TIDAK dibuang diam-diam:
    | nilai tersebut masuk laporan pemetaan pada preview import. Tambahkan alias
    | di sini bila HR sudah memutuskan pemetaannya.
    |
    */
    'aliases' => [
        'jenis_kelamin' => [
            'l' => 'Laki-laki', 'lk' => 'Laki-laki', 'laki laki' => 'Laki-laki', 'laki-laki' => 'Laki-laki',
            'pria' => 'Laki-laki', 'male' => 'Laki-laki', 'm' => 'Laki-laki',
            'p' => 'Perempuan', 'pr' => 'Perempuan', 'perempuan' => 'Perempuan', 'wanita' => 'Perempuan',
            'female' => 'Perempuan', 'f' => 'Perempuan',
        ],
        'agama' => [
            'katholik' => 'Katolik', 'katolik' => 'Katolik', 'protestan' => 'Kristen', 'kristen protestan' => 'Kristen',
            'kristen' => 'Kristen', 'islam' => 'Islam', 'hindu' => 'Hindu', 'budha' => 'Buddha', 'buddha' => 'Buddha',
            'konghucu' => 'Konghucu', 'khonghucu' => 'Konghucu',
        ],
        'status_nikah' => [
            'belum menikah' => 'Belum Menikah', 'belum kawin' => 'Belum Menikah', 'lajang' => 'Belum Menikah', 'single' => 'Belum Menikah',
            'menikah' => 'Menikah', 'kawin' => 'Menikah', 'married' => 'Menikah',
            'cerai hidup' => 'Cerai Hidup', 'cerai mati' => 'Cerai Mati',
        ],
        'gol_darah' => [
            'a' => 'A', 'b' => 'B', 'ab' => 'AB', 'o' => 'O', '-' => 'Tidak diketahui', 'tidak tahu' => 'Tidak diketahui',
        ],
        'education_level' => [
            'sd' => 'SD', 'smp' => 'SMP', 'sltp' => 'SMP', 'sma' => 'SMA/SMK', 'smk' => 'SMA/SMK', 'smu' => 'SMA/SMK',
            'stm' => 'SMA/SMK', 'slta' => 'SMA/SMK', 'sma/smk' => 'SMA/SMK',
            'd1' => 'D1', 'd2' => 'D2', 'd3' => 'D3', 'd4' => 'D4', 'diii' => 'D3', 'div' => 'D4',
            's1' => 'S1', 's2' => 'S2', 's3' => 'S3', 'sarjana' => 'S1',
            // Contoh pemetaan yang perlu dikonfirmasi HR sebelum diaktifkan:
            // 'diploma graduate' => 'D3',
        ],
    ],
];
