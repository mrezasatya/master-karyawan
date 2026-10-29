<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;

class RestoreData extends Command
{
    protected $signature = 'karyawan:restore {file : Path file backup .zip} {--force : Lewati konfirmasi}';

    protected $description = 'Memulihkan database dan foto dari file backup (MENGGANTI seluruh data saat ini)';

    public function handle(Backup $backup): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error('File tidak ditemukan.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Seluruh data saat ini akan DIGANTI dengan isi backup. Lanjutkan?')) {
            return self::FAILURE;
        }

        $counts = $backup->restore($file);
        foreach ($counts as $table => $count) {
            $this->line(str_pad($table, 28).$count);
        }
        $this->info('Pemulihan selesai.');

        return self::SUCCESS;
    }
}
