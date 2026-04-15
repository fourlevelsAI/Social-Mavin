<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    /**
     * Create a new team and assign current user as owner
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'nullable|in:free,pro,enterprise',
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'owner_id' => $request->user()->id,
            'plan' => $validated['plan'] ?? 'free',
        ]);

        return response()->json([
            'message' => 'Team created successfully',
            'team' => $team,
        ], 201);
    }

    /**
     * Invite a user to a team by email
     */
    public function invite(Request $request, Team $team): JsonResponse
    {
        // Check if user is team owner
        if ($team->owner_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'nullable|in:member,admin',
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if already a member
        if ($team->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'User is already a team member'], 400);
        }

        // Add user to team
        $team->members()->attach($user->id, [
            'role' => $validated['role'] ?? 'member',
        ]);

        return response()->json([
            'message' => 'User invited to team',
            'user' => $user,
            'role' => $validated['role'] ?? 'member',
        ], 201);
    }

    /**
     * List all team members with roles
     */
    public function members(Team $team): JsonResponse
    {
        $members = $team->allMembers()->map(function ($user) use ($team) {
            $role = 'member';
            if ($user->id === $team->owner_id) {
                $role = 'owner';
            } else {
                $teamMember = $team->members()->where('user_id', $user->id)->first();
                if ($teamMember) {
                    $role = $teamMember->pivot->role;
                }
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
            ];
        });

        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'members' => $members,
            'total_members' => $members->count(),
        ]);
    }

    /**
     * Remove a team member
     */
    public function removeMember(Request $request, Team $team, User $user): JsonResponse
    {
        // Check if requester is team owner
        if ($team->owner_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Cannot remove owner
        if ($user->id === $team->owner_id) {
            return response()->json(['error' => 'Cannot remove team owner'], 400);
        }

        // Check if user is actually a member
        if (!$team->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'User is not a team member'], 404);
        }

        // Remove user from team
        $team->members()->detach($user->id);

        return response()->json([
            'message' => 'Team member removed',
            'user_id' => $user->id,
        ]);
    }
}

