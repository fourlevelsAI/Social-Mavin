<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Services\WarmupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailWarmupController extends Controller
{
    public function __construct(private WarmupService $warmupService)
    {
    }

    /**
     * Show all email accounts with warmup health scores
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->query('team_id');
        
        if (!$teamId) {
            return response()->json(['error' => 'team_id is required'], 400);
        }

        $healthScores = $this->warmupService->getTeamHealthScores($teamId);

        return response()->json([
            'accounts' => $healthScores,
            'total_accounts' => count($healthScores),
        ]);
    }

    /**
     * Toggle warmup for an email account
     */
    public function toggle(EmailAccount $emailAccount): JsonResponse
    {
        try {
            $emailAccount->update([
                'warmup_enabled' => !$emailAccount->warmup_enabled,
            ]);

            $healthScore = $this->warmupService->getHealthScore($emailAccount);

            return response()->json([
                'message' => $emailAccount->warmup_enabled ? 'Warmup enabled' : 'Warmup disabled',
                'account' => $healthScore,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

