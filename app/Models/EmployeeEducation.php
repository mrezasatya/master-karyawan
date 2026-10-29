<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    use Auditable;

    protected $table = 'employee_education';

    protected string $auditEntity = 'employee_education';

    protected $fillable = ['employee_id', 'level', 'major', 'institution', 'graduation_year'];

    protected function casts(): array
    {
        return ['graduation_year' => 'integer'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
