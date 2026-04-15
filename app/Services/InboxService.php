<?php

namespace App\Services;

use App\Models\Reply;
use App\Models\Campaign;

class InboxService
{
    /**
     * Get unread reply count for a team
     */
    public function getUnreadCount(int $teamId): int
    {
        return Reply::whereHas('campaign', function ($query) use ($teamId) {
            $query->where('team_id', $teamId);
        })
        ->whereNull('read_at')
        ->count();
    }

    /**
     * Get all reply threads for a team, optionally filtered by channel
     */
    public function getThreads(int $teamId, ?string $channel = null)
    {
        $query = Reply::with(['lead', 'campaign'])
            ->whereHas('campaign', function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            });

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    /**
     * Get unread replies count by channel
     */
    public function getUnreadByChannel(int $teamId): array
    {
        $channels = ['email', 'linkedin', 'instagram'];
        $counts = [];

        foreach ($channels as $channel) {
            $counts[$channel] = Reply::where('channel', $channel)
                ->whereNull('read_at')
                ->whereHas('campaign', function ($q) use ($teamId) {
                    $q->where('team_id', $teamId);
                })
                ->count();
        }

        return $counts;
    }

    /**
     * Get conversation thread for a lead in a campaign
     */
    public function getConversationThread(int $leadId, int $campaignId)
    {
        return Reply::where('lead_id', $leadId)
            ->where('campaign_id', $campaignId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
