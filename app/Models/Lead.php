<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = ['campaign_id', 'email', 'first_name', 'last_name', 'company', 'linkedin_url', 'instagram_handle', 'status'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
