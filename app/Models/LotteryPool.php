<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryPool extends Model
{
    protected $fillable = ['event_id', 'name', 'criteria', 'status'];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'lottery_pool_participants');
    }

    public function winners(): HasMany
    {
        return $this->hasMany(LotteryWinner::class);
    }
}
