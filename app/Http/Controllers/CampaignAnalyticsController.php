<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\JsonResponse;

class CampaignAnalyticsController extends Controller
{
    /**
     * Show analytics for a specific campaign
     */
    public function show(Campaign $campaign): JsonResponse
    {
        // Total leads
        $totalLeads = $campaign->leads()->count();

        // Emails sent (leads with status 'contacted')
        $emailsSent = $campaign->leads()->where('status', 'contacted')->count();

        // Calls booked (leads with status 'booked')
        $callsBooked = $campaign->leads()->where('status', 'booked')->count();

        // Replies count
        $repliesCount = $campaign->replies()->count();

        // Calculate reply rate
        $replyRate = $emailsSent > 0 ? ($repliesCount / $emailsSent) * 100 : 0;

        // Open rate (placeholder, will need tracking pixel implementation)
        $openRate = 0;

        // Per sequence step performance
        $stepPerformance = $campaign->sequenceSteps()
            ->with('campaign.leads')
            ->get()
            ->map(function ($step) {
                return [
                    'step_number' => $step->step_number,
                    'channel' => $step->channel,
                    'subject' => $step->subject,
                    'emails_sent' => $step->campaign->leads()->where('status', 'contacted')->count(),
                ];
            });

        // Per channel breakdown (email vs linkedin vs instagram replies)
        $channelBreakdown = [
            'email' => $campaign->replies()->where('channel', 'email')->count(),
            'linkedin' => $campaign->replies()->where('channel', 'linkedin')->count(),
            'instagram' => $campaign->replies()->where('channel', 'instagram')->count(),
        ];

        return response()->json([
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'status' => $campaign->status,
            'total_leads' => $totalLeads,
            'emails_sent' => $emailsSent,
            'open_rate' => $openRate,
            'reply_rate' => round($replyRate, 2),
            'calls_booked' => $callsBooked,
            'total_replies' => $repliesCount,
            'step_performance' => $stepPerformance,
            'channel_breakdown' => $channelBreakdown,
            'created_at' => $campaign->created_at,
            'updated_at' => $campaign->updated_at,
        ]);
    }
}

