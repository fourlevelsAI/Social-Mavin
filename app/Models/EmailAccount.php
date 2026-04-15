<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAccount extends Model
{
    protected $fillable = ['team_id', 'email', 'smtp_host', 'smtp_port', 'warmup_enabled', 'warmup_score'];

    protected $casts = [
        'warmup_enabled' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
