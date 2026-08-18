<?php

namespace App\Models;

use App\Enums\ParticipantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'category_id',
        'pin_code',
        'transaction_id',
        'full_name',
        'id_card_number',
        'gender',
        'phone',
        'email',
        'bib_number',
        'status',
        'claimed_by',
        'claimed_at',
        'claimed_device',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ParticipantStatus::class,
            'claimed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function assignmentLogs(): HasMany
    {
        return $this->hasMany(BibAssignmentLog::class);
    }

    public function checkLogs(): HasMany
    {
        return $this->hasMany(BibCheckLog::class);
    }

    // --- Local scopes (Laravel best-practice: reusable query constraints) ---

    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->where('status', ParticipantStatus::Unclaimed);
    }

    public function scopeCheckedIn(Builder $query): Builder
    {
        return $query->where('status', ParticipantStatus::CheckedIn);
    }
}
