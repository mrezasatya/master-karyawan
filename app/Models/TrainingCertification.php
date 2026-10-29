<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCertification extends Model
{
    use Auditable;

    protected string $auditEntity = 'training_certification';

    protected $fillable = ['employee_id', 'item_type', 'item_name', 'year', 'organizer', 'certificate_number', 'issue_date', 'expiry_date'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'issue_date' => 'date', 'expiry_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
