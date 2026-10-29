<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Backup tingkat aplikasi (independen dari jenis database): seluruh tabel diekspor ke JSON
 * dan foto disertakan dalam satu file ZIP. Untuk produksi, IT disarankan tetap menjalankan
 * backup native database (pg_dump/mysqldump) sesuai kebijakan.
 */
class Backup
{
    /** Urutan sesuai dependensi foreign key. */
    public const TABLES = [
        'projects', 'employees', 'employee_education', 'employee_languages', 'training_certifications',
        'employee_projects', 'user_accounts', 'work_experiences', 'audit_logs', 'import_batches',
    ];

    public function directory(): string
    {
        $dir = Storage::disk('local')->path('backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return $dir;
    }

    public function create(): string
    {
        $path = $this->directory().'/backup-'.now()->format('Ymd-His').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat file backup.');
        }

        $manifest = ['created_at' => now()->toIso8601String(), 'driver' => DB::getDriverName(), 'tables' => []];
        foreach (self::TABLES as $table) {
            $rows = DB::table($table)->orderBy($this->key($table))->get()->map(fn ($r) => (array) $r)->all();
            $zip->addFromString("db/{$table}.json", json_encode($rows, JSON_UNESCAPED_UNICODE));
            $manifest['tables'][$table] = count($rows);
        }
        foreach (Storage::disk('local')->files('photos') as $file) {
            $zip->addFile(Storage::disk('local')->path($file), $file);
        }
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->close();
        @chmod($path, 0600);

        return $path;
    }

    public function prune(int $keep): int
    {
        $files = glob($this->directory().'/backup-*.zip');
        rsort($files);
        $removed = 0;
        foreach (array_slice($files, $keep) as $old) {
            unlink($old);
            $removed++;
        }

        return $removed;
    }

    /** @return array<string,int> jumlah baris per tabel yang dipulihkan */
    public function restore(string $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file) !== true || $zip->locateName('manifest.json') === false) {
            throw new RuntimeException('File backup tidak valid.');
        }

        $data = [];
        foreach (self::TABLES as $table) {
            $json = $zip->getFromName("db/{$table}.json");
            if ($json === false) {
                throw new RuntimeException("Tabel {$table} tidak ada di backup.");
            }
            $data[$table] = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        }

        $counts = [];
        Schema::withoutForeignKeyConstraints(function () use ($data, &$counts) {
            DB::transaction(function () use ($data, &$counts) {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }
                foreach (self::TABLES as $table) {
                    foreach (array_chunk($data[$table], 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                    $counts[$table] = count($data[$table]);
                }
            });
        });
        $this->resetSequences();
        DB::table('sessions')->delete();

        Storage::disk('local')->deleteDirectory('photos');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'photos/') && ! str_contains($name, '..') && ! str_ends_with($name, '/')) {
                Storage::disk('local')->put($name, $zip->getFromIndex($i));
            }
        }
        $zip->close();

        return $counts;
    }

    private function key(string $table): string
    {
        return $table === 'employees' ? 'employee_id' : 'id';
    }

    private function resetSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach (self::TABLES as $table) {
            $key = $this->key($table);
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', '{$key}'), COALESCE((SELECT MAX({$key}) FROM {$table}), 0) + 1, false)");
        }
    }
}
