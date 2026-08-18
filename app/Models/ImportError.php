<?php

namespace App\Models;

use App\Enums\ImportErrorReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    protected $fillable = ['import_batch_id', 'row_number', 'raw_data', 'reason', 'message'];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'reason' => ImportErrorReason::class,
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
