<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    protected $fillable = ['name', 'owner_id', 'plan'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function allMembers()
    {
        // Get both owner and team members
        $owner = User::find($this->owner_id);
        $memberUserIds = $this->members()->pluck('users.id')->toArray();

        if (!in_array($owner->id, $memberUserIds)) {
            $memberUserIds[] = $owner->id;
        }

        return User::whereIn('id', $memberUserIds)->get();
    }
}
