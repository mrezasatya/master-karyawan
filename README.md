# Master Data Karyawan Proyek & CV Generator — Bukaka

Web app untuk menggantikan Excel sebagai **master data karyawan proyek** (501–2.000 karyawan) dan menghasilkan **CV PDF** mengikuti template Bukaka. Dibangun berdasarkan *Brief MVP v1.2*.

> **Status:** MVP siap diuji (UAT) di lingkungan development/staging dengan **data dummy**. Hosting produksi, penggunaan data asli, dan kebijakan password menunggu persetujuan IT.

---

## 1. Fitur

| Modul | Isi |
|---|---|
| Login & keamanan | Username = NIK. Password awal karyawan = tanggal lahir **DDMMYY**, **wajib diganti** saat login pertama (tidak boleh memuat tanggal lahir/NIK). Batas 5 percobaan login, pesan error netral, sesi berakhir otomatis setelah 30 menit tidak aktif, tanpa "ingat saya". Otorisasi role diperiksa di backend. |
| Dashboard HR | Jumlah karyawan aktif/nonaktif, proyek aktif, pengingat kontrak 90/60/30 hari + "Lewat tanggal kontrak" (tanpa menonaktifkan otomatis). |
| Data Karyawan | Cari NIK/nama, filter status/proyek/jabatan/akhir kontrak, pagination. Profil bertab: Identitas & Administrasi, Pekerjaan & Kontrak (+ penugasan multi-proyek), Pendidikan & Bahasa, Foto, TRAINING, Pengalaman Kerja. Peringatan bila meninggalkan form yang belum disimpan. Nonaktifkan (data tetap disimpan). |
| Data Proyek | Master proyek (kode unik opsional, nama unik, status). Peringatan saat menonaktifkan proyek yang masih punya penugasan aktif. |
| Import / Export Excel | Unggah → validasi → **preview** (baru / diperbarui + perubahan field penting / duplikat / invalid / nilai tidak dikenali / proyek baru) → konfirmasi → simpan dalam satu transaksi → laporan kesalahan (.xlsx). Export ke template standar yang dapat di-import ulang. |
| Portal Karyawan | "Profil Saya": ringkasan profil dan tambah/edit/arsip pengalaman kerja milik sendiri, satu kolom di HP. Status simpan jelas; bila sinyal putus sistem mencoba ulang otomatis (maks. 4×) tanpa menghapus isian. |
| CV Generator | Checklist data wajib (memblokir unduhan) dan peringatan, preview, PDF multi-halaman mengikuti template Bukaka. Data administrasi sensitif **tidak pernah** dicetak. |
| Akun Pengguna | Buat akun per NIK atau massal, reset password (HR tidak dapat melihat password), aktif/nonaktif. |
| Audit Log | Create/Update/Deactivate/SoftDelete/Import/Export/GenerateCV/PasswordReset dan kejadian keamanan (akses ditolak, login terkunci). Hanya **nama field** yang dicatat, bukan nilainya. Tidak dapat diubah/dihapus. |
| Backup | `php artisan karyawan:backup` (terjadwal harian 01:30, retensi 14 file) dan `karyawan:restore`. |

## 2. Teknologi

Laravel 13 (PHP 8.3), Blade + Tailwind CSS 4 + Alpine.js, PostgreSQL (produksi) / SQLite (development), dompdf (PDF di server, tanpa Chrome), PhpSpreadsheet (Excel). Aset frontend hasil build sudah disertakan di `public/build`, sehingga server produksi **tidak memerlukan Node.js**.

## 3. Demo HTML (tanpa instalasi)

Buka file `demo/index.html` langsung di browser (klik dua kali). Isinya demo interaktif dengan data fiktif yang meniru seluruh alur aplikasi: login HR/karyawan, wajib ganti password, dashboard kontrak, data karyawan, proyek, import Excel (file dibaca di browser dan tidak dikirim ke mana pun), CV Generator, akun, dan audit log. Data hanya tersimpan sementara di browser; muat ulang halaman mengembalikan data ke awal. Pembacaan file Excel memerlukan koneksi internet untuk memuat pustaka SheetJS dari cdnjs. Unduhan PDF/Excel hanya tersedia di aplikasi asli.

Akun demo: HR `HR0001` / `demo123`. Akun karyawan dan password awalnya (tanggal lahir DDMMYY) ditampilkan di halaman login demo.

## 4. Menjalankan di komputer lokal

Prasyarat: PHP 8.3 (ekstensi `gd`, `zip`, `intl`, `mbstring`, `pdo_sqlite`/`pdo_pgsql`) dan Composer.

```bash
git clone https://github.com/mrezasatya/master-karyawan.git
cd master-karyawan
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # data DUMMY: 60 karyawan fiktif, 7 proyek
php artisan serve
```

Buka http://localhost:8000. Seeder menampilkan kredensial demo di terminal:
- **HR demo:** `HR0001` / password acak yang ditampilkan (atau isi `DEMO_HR_PASSWORD` di `.env` sebelum seeding).
- **Karyawan demo:** NIK `001001` dst. / tanggal lahir DDMMYY (ditampilkan di terminal).

Mengubah tampilan (CSS/JS)? Jalankan `npm install && npm run build`, lalu commit folder `public/build`.

## 5. Membuat akun HR pertama (lingkungan staging/produksi)

Jangan memakai seeder demo. Buat akun HR melalui terminal server; password diketik secara privat dan tidak tersimpan di kode atau log:

```bash
php artisan migrate --force
php artisan karyawan:buat-hr <NIK_HR> --nama="Nama HR"
```

Akun karyawan kemudian dibuat dari menu **Akun Pengguna** (satu per satu atau massal), atau dicentang saat proses import.

## 6. Import Excel

**Format yang didukung:**
1. **File master lama:** satu sheet, 32 kolom (`NIK, Nama, NamaPanggilan, JenisKelamin, …, email, Project`). Kolom `Project` diperlakukan sebagai penugasan utama (Aktif); `Pendidikan/Jurusan/Sekolah` menjadi satu riwayat pendidikan.
2. **Template standar** (menu Import → *Unduh Template Kosong*): sheet `Karyawan` (kolom lama + `Kewarganegaraan`, `StatusKaryawan`, `TahunLulus`, `Bahasa`), sheet `PenugasanProyek` (`NIK, KodeProyek, NamaProyek, TglMulai, TglSelesai, StatusPenugasan`), dan sheet `Petunjuk`.

**Aturan utama:** kolom wajib `NIK, Nama, JenisKelamin, Jobtitle, TglMasuk`. Pencocokan berdasarkan NIK (diperlakukan sebagai teks). NIK duplikat dalam satu file ditolak. Baris invalid dilewati tanpa menimpa data. **Sel kosong tidak menghapus data lama.** Profil yang tidak ada di file tidak dihapus. Proyek yang belum ada dibuat hanya bila HR mencentang konfirmasi.

**Nilai yang tidak dikenali** (Agama, Status Nikah, PTKP, Gol. Darah, Jenjang Pendidikan) tidak diimpor dan muncul di laporan pemetaan. Alias dapat ditambahkan di `config/karyawan.php` → `aliases`, misalnya `'katholik' => 'Katolik'` (sudah ada). Jenis kelamin yang tidak dikenali membuat baris gagal karena merupakan field wajib.

**Temuan pada file contoh `template_master_data_karyawan.xlsx`:**
- `NIK`, `NoKTP`, `KK`, `NPWP`, `BPJS` tersimpan sebagai **angka**. Excel hanya presisi 15 digit, sehingga No. KTP 16 digit berisiko kehilangan digit terakhir (contoh `1111111111111110`), dan NIK yang diawali angka 0 sudah kehilangan nol tersebut di file. Sistem memberi peringatan. **Format kolom tersebut sebagai Text** sebelum ekspor dari sistem lama.
- No. KK di file contoh hanya 11 digit. KTP/KK berupa angka yang bukan 16 digit **tidak diimpor** (peringatan), tetapi baris karyawan tetap masuk.
- Kolom `Status` (mis. "Karyawan Proyek") disimpan sebagai **kategori karyawan**, terpisah dari `StatusKaryawan` (Aktif/Nonaktif). Profil baru tanpa `StatusKaryawan` diset **Aktif** (diberi tahu di preview).
- `TahunLulus`, `Kewarganegaraan`, `Bahasa`, dan foto belum ada di file lama, sehingga perlu dilengkapi agar CV final dapat diunduh.

## 7. CV

- **Wajib untuk CV final:** nama, jenis kelamin, tanggal lahir, kewarganegaraan, jabatan, serta No. HP atau email. Bila belum lengkap, unduhan diblokir dan sistem tidak mengarang nilai.
- **Peringatan:** foto, pendidikan (dan tahun lulus), pengalaman, bahasa, TRAINING.
- Format: Date of Birth `Dec 04, 1993`, Sex `Male/Female`, pendidikan `Tahun - Jenjang - Jurusan - Institusi`, periode pengalaman `Mei 22 s/d Apr 24`. Pendidikan dan pengalaman diurutkan dari yang terbaru.
- Tata letak diukur dari template resmi (letter, Arial/Helvetica): judul & heading 12pt bold, isi 10pt, tabel TRAINING 13,5pt, posisi logo/foto/kolom mengikuti template. Uraian tanggung jawab dicetak 9pt di bawah tiap pengalaman.
- Jenjang pendidikan dicetak dengan label seperti template (D3 → "Diploma Graduate", S1 → "Bachelor Degree", dst.). Label dapat diubah di `config/karyawan.php` → `cv_education_labels`.
- Tidak dicetak: KTP, BPJS, NPWP, KK, nama ibu, agama, golongan darah, tinggi/berat, status nikah, alamat, PTKP, tempat lahir, nomor/jenis sertifikat.
- Template: `resources/views/cv/template.blade.php`. Logo: `public/images/logo-bukaka.png`.

## 8. Konfigurasi penting (`.env`)

| Variabel | Fungsi |
|---|---|
| `DB_CONNECTION`, `DB_*` | `sqlite` (dev) atau `pgsql`/`mysql` |
| `SESSION_LIFETIME=30` | Menit tidak aktif sebelum sesi berakhir |
| `SESSION_SECURE_COOKIE=true`, `FORCE_HTTPS=true` | Wajib di staging/produksi |
| `KARYAWAN_PASSWORD_AWAL` | `tanggal_lahir` (default, sesuai keputusan bisnis) atau `acak` |
| `KARYAWAN_BACKUP_KEEP` | Jumlah file backup yang disimpan |
| `APP_DEBUG=false`, `APP_ENV=production` | Wajib di produksi |

Ambang kontrak, pilihan dropdown, batas foto, dan pemetaan nilai lama ada di `config/karyawan.php`.

## 9. Backup & pemulihan

```bash
php artisan karyawan:backup
```

```bash
php artisan karyawan:restore storage/app/private/backups/backup-YYYYMMDD-HHMMSS.zip
```

Backup berisi seluruh tabel (JSON) dan foto dalam satu ZIP di `storage/app/private/backups` (permission 600). Jadwal harian aktif bila server menjalankan cron `* * * * * php artisan schedule:run`. File backup berisi data pribadi, jadi simpan salinan di lokasi terbatas sesuai kebijakan IT. Untuk produksi disarankan **juga** memakai backup native database (`pg_dump`). Pemulihan sudah diuji otomatis (`tests/Feature/BackupTest.php`).

## 10. Checklist untuk IT sebelum go-live

- [ ] Lokasi hosting (data center/cloud perusahaan), domain, HTTPS, firewall/VPN
- [ ] PostgreSQL produksi + akun DB khusus aplikasi; backup native + uji restore
- [ ] Web server (Nginx/Apache + PHP-FPM 8.3), document root ke folder `public/`
- [ ] `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`
- [ ] `php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Cron `schedule:run` untuk backup harian; folder `storage/` dapat ditulis oleh PHP dan tidak dapat diakses publik
- [ ] Provisioning akun HR dengan `php artisan karyawan:buat-hr`
- [ ] **Persetujuan kebijakan password tanggal lahir** (lihat catatan keamanan)
- [ ] Persetujuan pemrosesan data asli sesuai kebijakan pelindungan data perusahaan
- [ ] Penanggung jawab teknis dan operasional aplikasi

## 11. Catatan keamanan dan keputusan desain

- **Password awal = tanggal lahir (DDMMYY)** dipakai sesuai keputusan bisnis. Risikonya, tanggal lahir mudah diketahui orang lain. Mitigasinya: wajib ganti saat login pertama, rate limit login, password baru tidak boleh memuat tanggal lahir/NIK, dan status "belum diganti" terlihat di menu Akun. Selama akun belum diganti password-nya, siapa pun yang tahu NIK dan tanggal lahir dapat masuk lebih dulu. Karena itu, **bagikan akun secara bertahap dan minta karyawan segera login**. Alternatif yang lebih aman cukup diaktifkan lewat `KARYAWAN_PASSWORD_AWAL=acak`.
- Akun HR selalu memakai password acak/privat; tidak ada kredensial di repository.
- Karyawan hanya dapat mengubah pengalaman miliknya. Percobaan mengganti ID di URL ditolak (403) dan tercatat sebagai kejadian keamanan.
- Karyawan dapat **mengarsipkan** (soft delete) pengalaman miliknya sendiri. Bila hanya HR yang boleh mengarsipkan, ubah `ExperienceController::destroy`.
- Menonaktifkan karyawan juga menonaktifkan akun login-nya. Mengaktifkan kembali tidak mengaktifkan akun secara otomatis.
- Halaman yang berisi data pribadi dikirim dengan `Cache-Control: no-store`. Form tidak disimpan di `localStorage`.
- Foto disimpan privat, di-encode ulang (metadata EXIF terhapus), dan hanya dapat dibuka HR yang login.

## 12. Pengujian

```bash
php artisan test
```

Terdapat 35 feature test yang mencakup kriteria penerimaan MVP: hak akses backend, NIK unik dan leading zero, import (preview, duplikat, invalid, sel kosong, round-trip export→import), multi-proyek, validasi pengalaman, CV tanpa data sensitif dan multi-halaman, dashboard kontrak, serta backup/restore. CI GitHub Actions menjalankan test di SQLite dan PostgreSQL.

## 13. Di luar cakupan MVP

Payroll, absensi, rekrutmen, penilaian kinerja, persetujuan berjenjang, notifikasi email/WhatsApp, banyak desain CV, mode offline penuh, dan riwayat kontrak lengkap.
