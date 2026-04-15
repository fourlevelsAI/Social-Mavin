<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use App\Services\InboxService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReplyController extends Controller
{
    public function __construct(private InboxService $inboxService)
    {
    }

    /**
     * List all replies for a team, optionally filtered by channel
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->query('team_id');
        $channel = $request->query('channel'); // email, linkedin, instagram

        if (!$teamId) {
            return response()->json(['error' => 'team_id is required'], 400);
        }

        $unreadCount = $this->inboxService->getUnreadCount($teamId);
        $unreadByChannel = $this->inboxService->getUnreadByChannel($teamId);
        $threads = $this->inboxService->getThreads($teamId, $channel);

        return response()->json([
            'unread_count' => $unreadCount,
            'unread_by_channel' => $unreadByChannel,
            'threads' => $threads,
        ]);
    }

    /**
     * Get a single reply with full conversation thread
     */
    public function show(Reply $reply): JsonResponse
    {
        $thread = $this->inboxService->getConversationThread($reply->lead_id, $reply->campaign_id);

        return response()->json([
            'current_reply' => $reply->load(['lead', 'campaign']),
            'thread' => $thread,
        ]);
    }

    /**
     * Mark a reply as read
     */
    public function markRead(Reply $reply): JsonResponse
    {
        try {
            $reply->markAsRead();

            return response()->json([
                'message' => 'Reply marked as read',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a reply back to the lead
     */
    public function reply(Request $request, Reply $reply): JsonResponse
    {
        try {
            $validated = $request->validate([
                'body' => 'required|string',
                'subject' => 'nullable|string',
            ]);

            // Create a new reply record for the outbound response
            $outboundReply = Reply::create([
                'lead_id' => $reply->lead_id,
                'campaign_id' => $reply->campaign_id,
                'channel' => $reply->channel,
                'subject' => $validated['subject'] ?? 'RE: ' . ($reply->subject ?? 'Reply'),
                'body' => $validated['body'],
                'from_email' => $reply->from_email, // Echo back to same channel
                'replied_at' => now(),
            ]);

            // Mark original as replied
            $reply->markAsReplied();

            return response()->json([
                'message' => 'Reply sent successfully',
                'reply' => $outboundReply,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

