<?php

use Illuminate\Support\Facades\Schedule;

// Backup harian; jalankan "php artisan schedule:work" atau cron "* * * * * php artisan schedule:run".
Schedule::command('karyawan:backup')->dailyAt('01:30');
