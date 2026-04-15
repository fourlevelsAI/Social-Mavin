<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->query('team_id');
        
        if (!$teamId) {
            return response()->json(['error' => 'team_id is required'], 400);
        }

        $emailAccounts = EmailAccount::where('team_id', $teamId)->get();

        return response()->json($emailAccounts);
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
            'email' => 'required|email|unique:email_accounts',
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer',
            'warmup_enabled' => 'nullable|boolean',
            'warmup_score' => 'nullable|integer|min:0|max:100',
        ]);

        $emailAccount = EmailAccount::create($validated);

        return response()->json($emailAccount, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailAccount $emailAccount): JsonResponse
    {
        $emailAccount->delete();

        return response()->json(null, 204);
    }
}
