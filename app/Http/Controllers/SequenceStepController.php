<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\SequenceStep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SequenceStepController extends Controller
{
    /**
     * Store sequence steps for a campaign
     */
    public function store(Request $request, Campaign $campaign): JsonResponse
    {
        try {
            // Validate authorization
            if ($campaign->team_id !== auth()->user()->teams()->first()?->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Validate the request
            $validated = $request->validate([
                'steps' => 'required|array|min:1',
                'steps.*.step_order' => 'required|integer|min:1',
                'steps.*.channel' => 'required|string|in:email,linkedin,instagram',
                'steps.*.subject' => 'required|string|max:255',
                'steps.*.body' => 'required|string',
                'steps.*.delay_days' => 'required|integer|min:0',
            ]);

            // Delete existing sequence steps for this campaign
            $campaign->sequenceSteps()->delete();

            // Create new sequence steps
            $steps = [];
            foreach ($validated['steps'] as $step) {
                $createdStep = SequenceStep::create([
                    'campaign_id' => $campaign->id,
                    'step_number' => $step['step_order'],
                    'channel' => $step['channel'],
                    'subject' => $step['subject'],
                    'body' => $step['body'],
                    'delay_days' => $step['delay_days'],
                ]);
                $steps[] = $createdStep;
            }

            return response()->json([
                'message' => 'Sequence steps saved successfully',
                'campaign_id' => $campaign->id,
                'steps' => $steps,
                'count' => count($steps),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Sequence step save error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to save sequence steps',
            ], 400);
        }
    }
}
