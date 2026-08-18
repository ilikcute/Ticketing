<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibAssignmentLog extends Model
{
    protected $fillable = ['participant_id', 'bib_number', 'action', 'performed_by', 'notes'];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
