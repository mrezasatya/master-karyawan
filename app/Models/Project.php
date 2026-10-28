<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use Auditable, HasFactory;

    protected string $auditEntity = 'project';

    protected $fillable = ['project_code', 'project_name', 'status'];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeProject::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Aktif');
    }

    public function label(): string
    {
        return $this->project_code ? "{$this->project_code} — {$this->project_name}" : $this->project_name;
    }
}
