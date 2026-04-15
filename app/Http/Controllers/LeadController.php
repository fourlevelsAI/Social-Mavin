<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $campaignId = $request->query('campaign_id');
        
        if (!$campaignId) {
            return response()->json(['error' => 'campaign_id is required'], 400);
        }

        $leads = Lead::where('campaign_id', $campaignId)->get();

        return response()->json($leads);
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
            'campaign_id' => 'required|exists:campaigns,id',
            'email' => 'nullable|email',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url',
            'instagram_handle' => 'nullable|string|max:255',
            'status' => 'nullable|string',
        ]);

        $lead = Lead::create($validated);

        return response()->json($lead, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead->load('campaign'));
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
    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'sometimes|email',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'company' => 'sometimes|string|max:255',
            'linkedin_url' => 'sometimes|url',
            'instagram_handle' => 'sometimes|string|max:255',
            'status' => 'sometimes|string',
        ]);

        $lead->update($validated);

        return response()->json($lead);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(null, 204);
    }
}
