<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    protected $fillable = [
        'file_name', 'stored_path', 'uploaded_by', 'total_rows', 'created_count', 'updated_count',
        'failed_count', 'status', 'error_summary', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['error_summary' => 'array', 'processed_at' => 'datetime'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(UserAccount::class, 'uploaded_by');
    }
}
