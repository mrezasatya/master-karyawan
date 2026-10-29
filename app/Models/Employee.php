<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use Auditable, HasFactory;

    protected $primaryKey = 'employee_id';

    protected string $auditEntity = 'employee';

    /** Field yang boleh diisi HR melalui form/import. */
    public const EDITABLE = [
        'nik', 'nama', 'nama_panggilan', 'jenis_kelamin', 'kode_jbt', 'jobtitle', 'kategori_karyawan', 'status_awal',
        'tgl_masuk', 'kontrak_ke', 'akhir_kontrak', 'status_karyawan', 'tgl_lahir', 'tempat_lahir', 'kewarganegaraan',
        'alamat', 'gol_darah', 'tinggi_badan_cm', 'berat_badan_kg', 'agama', 'no_ktp', 'no_bpjs_tk', 'status_nikah',
        'npwp', 'no_bpjs_kes', 'kk', 'nama_ibu', 'status_ptkp', 'no_hp', 'email',
    ];

    protected $fillable = [...self::EDITABLE, 'foto_path'];

    protected function casts(): array
    {
        return [
            'tgl_masuk' => 'date',
            'akhir_kontrak' => 'date',
            'tgl_lahir' => 'date',
            'tinggi_badan_cm' => 'integer',
            'berat_badan_kg' => 'decimal:1',
        ];
    }

    public function education(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class, 'employee_id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(EmployeeLanguage::class, 'employee_id');
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(TrainingCertification::class, 'employee_id');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class, 'employee_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeProject::class, 'employee_id');
    }

    public function activeProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'employee_projects', 'employee_id', 'project_id')
            ->wherePivot('assignment_status', 'Aktif');
    }

    public function account(): HasOne
    {
        return $this->hasOne(UserAccount::class, 'employee_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status_karyawan', 'Aktif');
    }

    public function isActive(): bool
    {
        return $this->status_karyawan === 'Aktif';
    }

    /** Sisa hari menuju akhir kontrak (negatif bila sudah lewat). */
    public function contractDaysLeft(): ?int
    {
        if (! $this->akhir_kontrak) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->akhir_kontrak, false);
    }

    /** Label pengingat kontrak berdasarkan ambang 90/60/30 hari. */
    public function contractReminder(): ?string
    {
        $days = $this->contractDaysLeft();
        if ($days === null) {
            return null;
        }
        if ($days < 0) {
            return 'Lewat tanggal kontrak';
        }
        foreach (config('karyawan.contract_thresholds') as $threshold) {
            if ($days <= $threshold) {
                return "≤ {$threshold} hari";
            }
        }

        return null;
    }
}
