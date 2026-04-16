<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\CampaignSequenceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(private CampaignSequenceService $sequenceService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->query('team_id');
        
        if (!$teamId) {
            return response()->json(['error' => 'team_id is required'], 400);
        }

        $campaigns = Campaign::where('team_id', $teamId)
            ->with(['leads', 'sequenceSteps'])
            ->get();

        return response()->json($campaigns);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'channels' => 'nullable|array',
            'status' => 'nullable|in:draft,active,paused',
        ]);

        $campaign = Campaign::create($validated);

        return response()->json($campaign, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        return response()->json($campaign->load(['team', 'leads', 'sequenceSteps']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'channels' => 'nullable|array',
            'status' => 'nullable|in:draft,active,paused',
        ]);

        $campaign->update($validated);

        return response()->json($campaign);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json(null, 204);
    }

    /**
     * Launch a campaign by dispatching emails to all leads
     */
    public function launch(Campaign $campaign): JsonResponse
    {
        try {
            // Validate campaign is authorized
            if ($campaign->team_id !== auth()->user()->teams()->first()?->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Launch the campaign and get detailed response
            $result = $this->sequenceService->launch($campaign);

            return response()->json([
                'message' => $result['message'],
                'campaign' => $campaign->fresh(),
                'details' => [
                    'leads_count' => $result['leads_count'],
                    'steps_count' => $result['steps_count'],
                    'jobs_dispatched' => $result['jobs_dispatched'],
                ],
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error('Campaign launch error: ' . $e->getMessage(), [
                'campaign_id' => $campaign->id,
                'user_id' => auth()->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to launch campaign. ' . $e->getMessage(),
            ], 400);
        }
    }
}

