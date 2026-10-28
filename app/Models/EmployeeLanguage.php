<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class EmployeeLanguage extends Model
{
    use Auditable;

    public const UPDATED_AT = null;

    protected string $auditEntity = 'employee_language';

    protected $fillable = ['employee_id', 'language_name'];
}
