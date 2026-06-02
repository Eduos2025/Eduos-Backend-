<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Qs;
use App\Helpers\Usr;
use App\Http\Requests\Support\TicketCreate;
use App\Repositories\TicketRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiTicketController extends ApiBaseController
{
    protected $ticket, $user;

    public function __construct(TicketRepo $ticket, UserRepo $user)
    {
        $this->ticket = $ticket;
        $this->user = $user;
    }

    /**
     * Display a listing of the user's tickets.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $where = ['tenant_id' => tenant('id'), 'user_id' => auth()->id()];
        $tickets = $this->ticket->getTickets($where);

        $active = [];
        $archived = [];

        foreach ($tickets->where('is_archived', false) as $tk) {
            $data = $tk->toArray();
            $data['id'] = Qs::hash($tk->id);
            $data['category_id'] = Qs::hash($tk->category_id);
            $active[] = $data;
        }

        foreach ($tickets->where('is_archived', true) as $tk) {
            $data = $tk->toArray();
            $data['id'] = Qs::hash($tk->id);
            $data['category_id'] = Qs::hash($tk->category_id);
            $archived[] = $data;
        }

        return $this->sendResponse([
            'active_tickets'   => $active,
            'archived_tickets' => $archived,
        ], 'Tickets list retrieved successfully.');
    }

    /**
     * Store a newly created ticket.
     *
     * @param TicketCreate $request
     * @return JsonResponse
     */
    public function store(TicketCreate $request): JsonResponse
    {
        $ticket_data = $request->only(['department', 'priority', 'subject', 'category_id']);
        $ticket_data['tenant_id'] = tenant('id');
        $ticket_data['user_id'] = auth()->id();
        $ticket_data['labels_ids'] = serialize($request->labels_ids ?? []);

        try {
            $ticket = $this->ticket->createTicket($ticket_data);

            $is_from_tenant = Usr::tenancyInitilized();
            $message_data = [
                'ticket_id'      => $ticket->id,
                'user_id'        => auth()->id(),
                'body'           => $request->message,
                'is_from_tenant' => $is_from_tenant
            ];
            $this->ticket->createMessage($message_data);

            $responseData = $ticket->toArray();
            $responseData['id'] = Qs::hash($ticket->id);

            return $this->sendResponse($responseData, 'Ticket created successfully.', 211);
        } catch (\Exception $e) {
            if (isset($ticket->id)) {
                $this->ticket->deleteTicket($ticket->id);
            }
            return $this->sendError('System Error.', ['error' => 'Could not create ticket. Please try again.'], 500);
        }
    }

    /**
     * Show support ticket reply log and history.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $realId = Qs::decodeHash($id);
        if (!$realId) {
            return $this->sendError('Invalid Ticket ID format.', [], 400);
        }

        $where_ticket = ['tenant_id' => tenant('id'), 'user_id' => auth()->id(), 'id' => $realId];
        $tickets = $this->ticket->getTickets($where_ticket);

        if ($tickets->isEmpty()) {
            return $this->sendError('Ticket not found.', [], 404);
        }

        $ticket = $tickets->first();
        
        $where_msg = ['ticket_id' => $realId];
        $messages = $this->ticket->whereMessages($where_msg)->orderBy('created_at', 'asc')->get();
        $messagesArray = [];

        foreach ($messages as $msg) {
            $messagesArray[] = [
                'id'             => Qs::hash($msg->id),
                'body'           => $msg->body,
                'created_at'     => $msg->created_at->toIso8601String(),
                'is_from_tenant' => $msg->is_from_tenant,
                'sender'         => $msg->user ? [
                    'id'   => Qs::hash($msg->user->id),
                    'name' => $msg->user->name,
                ] : null,
            ];
        }

        $ticketData = $ticket->toArray();
        $ticketData['id'] = Qs::hash($ticket->id);

        return $this->sendResponse([
            'ticket'   => $ticketData,
            'messages' => $messagesArray,
        ], 'Ticket details retrieved successfully.');
    }

    /**
     * Post a reply message to an active ticket.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $realId = Qs::decodeHash($id);
        if (!$realId) {
            return $this->sendError('Invalid Ticket ID format.', [], 400);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:400',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $where_ticket = ['tenant_id' => tenant('id'), 'user_id' => auth()->id(), 'id' => $realId];
        $tickets = $this->ticket->getTickets($where_ticket);

        if ($tickets->isEmpty()) {
            return $this->sendError('Ticket not found.', [], 404);
        }

        $ticket = $tickets->first();
        if ($ticket->status === 'closed') {
            return $this->sendError('Conflict.', ['error' => 'This ticket is closed and cannot receive replies.'], 409);
        }

        $is_from_tenant = Usr::tenancyInitilized();
        $message_data = [
            'ticket_id'      => $realId,
            'user_id'        => auth()->id(),
            'body'           => $request->message,
            'is_from_tenant' => $is_from_tenant
        ];

        $message = $this->ticket->createMessage($message_data);
        $this->ticket->updateTicket($realId, ['status' => 'replied']);

        $responseData = $message->toArray();
        $responseData['id'] = Qs::hash($message->id);

        return $this->sendResponse($responseData, 'Reply posted successfully.');
    }

    /**
     * Mark a ticket as closed.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function close(string $id): JsonResponse
    {
        $realId = Qs::decodeHash($id);
        if (!$realId) {
            return $this->sendError('Invalid Ticket ID format.', [], 400);
        }

        $where_ticket = ['tenant_id' => tenant('id'), 'user_id' => auth()->id(), 'id' => $realId];
        $tickets = $this->ticket->getTickets($where_ticket);

        if ($tickets->isEmpty()) {
            return $this->sendError('Ticket not found.', [], 404);
        }

        $this->ticket->updateTicket($realId, ['status' => 'closed']);

        return $this->sendResponse([], 'Ticket marked as closed successfully.');
    }
}
