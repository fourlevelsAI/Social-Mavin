<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenceStep extends Model
{
    protected $fillable = ['campaign_id', 'step_number', 'channel', 'subject', 'body', 'delay_days'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
