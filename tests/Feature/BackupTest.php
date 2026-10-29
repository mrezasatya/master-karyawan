<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Support\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_can_be_restored(): void
    {
        Storage::fake('local');
        $e = Employee::factory()->create(['nik' => '000042', 'nama' => 'Sebelum Backup']);
        $e->experiences()->create(['experience_type' => 'Sebelum bergabung', 'company_name' => 'PT A', 'position' => 'X', 'start_month' => 1, 'start_year' => 2010, 'end_month' => 1, 'end_year' => 2011]);
        Storage::disk('local')->put('photos/a.jpg', 'foto');

        $backup = app(Backup::class);
        $file = $backup->create();

        $e->update(['nama' => 'Diubah Setelah Backup']);
        Employee::factory()->count(3)->create();
        Storage::disk('local')->delete('photos/a.jpg');

        $counts = $backup->restore($file);

        $this->assertSame(1, $counts['employees']);
        $this->assertSame(1, Employee::count());
        $this->assertSame('Sebelum Backup', Employee::first()->nama);
        $this->assertSame('000042', Employee::first()->nik);
        $this->assertSame(1, Employee::first()->experiences()->count());
        Storage::disk('local')->assertExists('photos/a.jpg');
    }
}
