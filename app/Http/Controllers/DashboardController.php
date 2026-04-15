<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Reply;
use App\Models\EmailAccount;
use App\Services\WarmupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private WarmupService $warmupService)
    {
    }

    /**
     * Show team-wide dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->query('team_id');

        if (!$teamId) {
            return response()->json(['error' => 'team_id is required'], 400);
        }

        // Campaign counts by status
        $campaigns = Campaign::where('team_id', $teamId)->get();
        $campaignStats = [
            'total' => $campaigns->count(),
            'active' => $campaigns->where('status', 'active')->count(),
            'paused' => $campaigns->where('status', 'paused')->count(),
            'draft' => $campaigns->where('status', 'draft')->count(),
        ];

        // Total leads across all campaigns
        $totalLeads = Lead::whereIn('campaign_id', $campaigns->pluck('id'))
            ->count();

        // Total leads contacted
        $leadsContacted = Lead::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('status', 'contacted')
            ->count();

        // Total calls booked
        $callsBooked = Lead::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('status', 'booked')
            ->count();

        // Total replies this week
        $repliesThisWeek = Reply::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        // Total calls booked this week
        $callsBookedThisWeek = Lead::whereIn('campaign_id', $campaigns->pluck('id'))
            ->where('status', 'booked')
            ->where('updated_at', '>=', now()->subWeek())
            ->count();

        // Warmup health summary
        $emailAccounts = EmailAccount::where('team_id', $teamId)->get();
        $warmupHealthSummary = [
            'green' => 0,
            'amber' => 0,
            'red' => 0,
        ];

        foreach ($emailAccounts as $account) {
            $health = $this->warmupService->getHealthScore($account);
            $warmupHealthSummary[$health['status']]++;
        }

        return response()->json([
            'team_id' => $teamId,
            'campaigns' => $campaignStats,
            'total_leads' => $totalLeads,
            'leads_contacted' => $leadsContacted,
            'calls_booked' => $callsBooked,
            'replies_this_week' => $repliesThisWeek,
            'calls_booked_this_week' => $callsBookedThisWeek,
            'warmup_health' => $warmupHealthSummary,
            'total_email_accounts' => $emailAccounts->count(),
            'timestamp' => now(),
        ]);
    }
}

