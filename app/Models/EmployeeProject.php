<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProject extends Model
{
    use Auditable;

    protected string $auditEntity = 'employee_project';

    protected $fillable = ['employee_id', 'project_id', 'assignment_start', 'assignment_end', 'assignment_status'];

    protected function casts(): array
    {
        return ['assignment_start' => 'date', 'assignment_end' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
