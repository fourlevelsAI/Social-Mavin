<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SequenceStep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SequenceStepController extends Controller
{
    /**
     * Store sequence steps for a campaign
     */
    public function store(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Log the request for debugging
            Log::info('Sequence steps request', [
                'user_id' => $user?->id,
                'campaign_id' => $campaign->id,
                'request_keys' => array_keys($request->all()),
                'request_body' => $request->all(),
            ]);

            // Validate authorization - check if user owns the team that owns the campaign
            $userTeamIds = $user->teams()->pluck('id')->toArray();
            if (!in_array($campaign->team_id, $userTeamIds)) {
                Log::warning('Unauthorized sequence steps access', [
                    'user_id' => $user->id,
                    'campaign_id' => $campaign->id,
                    'user_teams' => $userTeamIds,
                ]);
                return response()->json(['error' => 'Unauthorized - you do not own this campaign'], 403);
            }

            // Accept both 'steps' and 'sequence_steps' as request keys
            $stepsData = $request->input('steps') ?? $request->input('sequence_steps', []);

            if (empty($stepsData)) {
                return response()->json([
                    'error' => 'No steps provided',
                    'message' => 'Provide an array of steps with key "steps" or "sequence_steps"',
                ], 422);
            }

            // Delete existing sequence steps for this campaign
            $campaign->sequenceSteps()->delete();

            // Create new sequence steps
            $steps = [];
            foreach ($stepsData as $index => $step) {
                try {
                    $createdStep = SequenceStep::create([
                        'campaign_id' => $campaign->id,
                        'step_number' => $step['step_order'] ?? $step['step_number'] ?? ($index + 1),
                        'channel' => $step['channel'] ?? 'email',
                        'subject' => $step['subject'] ?? 'No Subject',
                        'body' => $step['body'] ?? '',
                        'delay_days' => $step['delay_days'] ?? 0,
                    ]);
                    $steps[] = $createdStep;
                } catch (\Exception $e) {
                    Log::error('Error creating sequence step', [
                        'campaign_id' => $campaign->id,
                        'step_index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            Log::info('Sequence steps saved successfully', [
                'campaign_id' => $campaign->id,
                'steps_count' => count($steps),
            ]);

            return response()->json([
                'message' => 'Sequence steps saved successfully',
                'campaign_id' => $campaign->id,
                'steps' => $steps,
                'count' => count($steps),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Sequence step save error', [
                'campaign_id' => $campaign->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to save sequence steps',
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTrace() : null,
            ], 400);
        }
    }
}
