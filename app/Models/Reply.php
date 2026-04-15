<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reply extends Model
{
    protected $fillable = ['lead_id', 'campaign_id', 'channel', 'subject', 'body', 'from_email', 'read_at', 'replied_at'];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsReplied(): void
    {
        $this->update(['replied_at' => now()]);
    }
}
