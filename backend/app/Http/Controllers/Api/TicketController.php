<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets for the authenticated user's organization.
     */
    public function index(Request $request)
    {
        $tickets = Ticket::forOrganization($request->user()->org_id)
            ->with(['requester', 'assignee'])
            ->get();

        return response()->json($tickets);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'tags' => 'array',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::create([
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'open',
            'tags' => $request->tags,
            'requester_id' => $request->user()->id,
            'assignee_id' => $request->assignee_id,
            'org_id' => $request->user()->org_id,
        ]);

        return response()->json($ticket->load(['requester', 'assignee']), 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, $id)
    {
        $ticket = Ticket::forOrganization($request->user()->org_id)
            ->with(['requester', 'assignee', 'replies.user'])
            ->findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::forOrganization($request->user()->org_id)->findOrFail($id);

        // Only admin and assigned agent can update
        if ($request->user()->role === 'customer' && $ticket->requester_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'tags' => 'sometimes|array',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket->update($request->only(['subject', 'description', 'status', 'priority', 'tags', 'assignee_id']));

        return response()->json($ticket->load(['requester', 'assignee']));
    }

    /**
     * Remove the specified ticket (admin only).
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ticket = Ticket::forOrganization($request->user()->org_id)->findOrFail($id);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Add a reply to a ticket.
     */
    public function addReply(Request $request, $id)
    {
        $ticket = Ticket::forOrganization($request->user()->org_id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return response()->json($reply->load('user'), 201);
    }
}