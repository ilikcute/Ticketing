<?php

namespace App\Models;

use App\Enums\ImportMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'event_id', 'file_name', 'uploaded_by', 'total_rows',
        'success_count', 'skipped_duplicate_count', 'failed_count',
        'mode', 'status',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ImportMode::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }
}
