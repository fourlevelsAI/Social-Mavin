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

            // Accept both 'steps' and 'sequence_steps' as request keys
            $stepsData = $request->input('steps') ?? $request->input('sequence_steps', []);

            // Validate the request
            $validated = $request->validate([
                'steps' => 'nullable|array|min:1',
                'sequence_steps' => 'nullable|array|min:1',
                'steps.*.step_order' => 'nullable|integer|min:1',
                'steps.*.channel' => 'nullable|string|in:email,linkedin,instagram',
                'steps.*.subject' => 'nullable|string|max:255',
                'steps.*.body' => 'nullable|string',
                'steps.*.delay_days' => 'nullable|integer|min:0',
                'sequence_steps.*.step_order' => 'nullable|integer|min:1',
                'sequence_steps.*.channel' => 'nullable|string|in:email,linkedin,instagram',
                'sequence_steps.*.subject' => 'nullable|string|max:255',
                'sequence_steps.*.body' => 'nullable|string',
                'sequence_steps.*.delay_days' => 'nullable|integer|min:0',
            ]);

            // Use whichever key was provided
            $stepsToSave = $stepsData;
            
            if (empty($stepsToSave)) {
                return response()->json([
                    'error' => 'No steps provided',
                    'message' => 'Provide an array of steps with key "steps" or "sequence_steps"',
                ], 422);
            }

            // Delete existing sequence steps for this campaign
            $campaign->sequenceSteps()->delete();

            // Create new sequence steps
            $steps = [];
            foreach ($stepsToSave as $step) {
                $createdStep = SequenceStep::create([
                    'campaign_id' => $campaign->id,
                    'step_number' => $step['step_order'] ?? $step['step_number'] ?? 1,
                    'channel' => $step['channel'] ?? 'email',
                    'subject' => $step['subject'] ?? '',
                    'body' => $step['body'] ?? '',
                    'delay_days' => $step['delay_days'] ?? 0,
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
            \Illuminate\Support\Facades\Log::error('Sequence step save error: ' . $e->getMessage(), [
                'campaign_id' => $campaign->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to save sequence steps',
            ], 400);
        }
    }
}
