<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class WorkExperience extends Model
{
    use Auditable, SoftDeletes;

    protected string $auditEntity = 'work_experience';

    protected $fillable = [
        'employee_id', 'experience_type', 'company_name', 'project_name', 'position',
        'start_month', 'start_year', 'end_month', 'end_year', 'is_current', 'responsibilities',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'start_month' => 'integer', 'start_year' => 'integer',
            'end_month' => 'integer', 'end_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $exp) {
            $exp->created_by ??= Auth::id();
            $exp->updated_by ??= Auth::id();
        });
        static::updating(function (self $exp) {
            if (Auth::check()) {
                $exp->updated_by = Auth::id();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'updated_by');
    }

    /** Urutan terbaru lebih dulu: yang masih berjalan, lalu berdasarkan periode mulai. */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_current')->orderByDesc('start_year')->orderByDesc('start_month');
    }

    public function periodLabel(): string
    {
        $start = Format::monthYear($this->start_month, $this->start_year);
        $end = $this->is_current ? 'Sekarang' : Format::monthYear($this->end_month, $this->end_year);

        return "{$start} s/d {$end}";
    }

    /** Uraian ringkas untuk CV, contoh: "Supervisor di Project CSP IKN". */
    public function cvTitle(): string
    {
        if ($this->experience_type === 'Selama bekerja') {
            return "{$this->position} di ".($this->project_name ?: $this->company_name);
        }

        return "{$this->position} di {$this->company_name}".($this->project_name ? " — {$this->project_name}" : '');
    }
}
