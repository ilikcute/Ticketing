<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'name', 'bib_prefix', 'bib_start', 'bib_end', 'quota'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Nomor BIB berikutnya yang tersedia untuk kategori ini.
     * Dipakai sebagai auto-suggest di form assign BIB (FR-08).
     */
    public function nextSuggestedBibNumber(): ?string
    {
        $lastNumber = $this->participants()
            ->whereNotNull('bib_number')
            ->selectRaw('MAX(CAST(REPLACE(bib_number, ?, "") AS UNSIGNED)) as max_num', [$this->bib_prefix])
            ->value('max_num');

        $nextNumber = $lastNumber ? $lastNumber + 1 : $this->bib_start;

        if ($nextNumber > $this->bib_end) {
            return null; // kuota BIB kategori ini habis
        }

        return $this->bib_prefix.$nextNumber;
    }
}
