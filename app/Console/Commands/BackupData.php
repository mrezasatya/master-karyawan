<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;

class BackupData extends Command
{
    protected $signature = 'karyawan:backup';

    protected $description = 'Membuat backup database (semua tabel aplikasi) dan foto ke storage/app/private/backups';

    public function handle(Backup $backup): int
    {
        $path = $backup->create();
        $removed = $backup->prune(config('karyawan.backup_keep'));
        $this->info('Backup dibuat: '.$path);
        if ($removed) {
            $this->line("{$removed} backup lama dihapus (retensi ".config('karyawan.backup_keep').' file).');
        }

        return self::SUCCESS;
    }
}
