<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Helpers\Qs;
use App\Http\Requests\UserRequest;
use App\Models\Message;
use App\Models\StudentRecord;
use App\Models\Thread;
use App\Notifications\MessageSent;
use App\User;
use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Participant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ApiMessageController extends ApiBaseController
{
    /**
     * Get list of conversations (threads) for the authenticated user.
     *
     * @return JsonResponse
     */
    public function threads(): JsonResponse
    {
        $userId = auth()->id();
        
        // Retrieve all threads user is participating in
        $threads = Thread::forUser($userId)->latest('updated_at')->get();
        $threadsArray = [];

        foreach ($threads as $t) {
            $latestMessage = $t->latestMessage;
            $unreadCount = $t->userUnreadMessagesCount($userId);

            $threadsArray[] = [
                'id'             => Qs::hash($t->id),
                'subject'        => $t->subject,
                'created_at'     => $t->created_at->toIso8601String(),
                'updated_at'     => $t->updated_at->toIso8601String(),
                'unread_count'   => $unreadCount,
                'latest_message' => $latestMessage ? [
                    'body'       => $latestMessage->body,
                    'created_at' => $latestMessage->created_at->toIso8601String(),
                    'sender'     => $latestMessage->user ? [
                        'id'   => Qs::hash($latestMessage->user->id),
                        'name' => $latestMessage->user->name,
                    ] : null,
                ] : null,
            ];
        }

        return $this->sendResponse($threadsArray, 'Conversation threads retrieved successfully.');
    }

    /**
     * Get all messages in a conversation thread.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function threadMessages(string $id): JsonResponse
    {
        $realThreadId = Qs::decodeHash($id);
        if (!$realThreadId) {
            return $this->sendError('Invalid Thread ID format.', [], 400);
        }

        try {
            $thread = Thread::findOrFail($realThreadId);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Thread not found.', [], 404);
        }

        $userId = auth()->id();
        $thread->markAsRead($userId);

        $messages = $thread->messages()->with('user')->orderBy('created_at', 'asc')->get();
        $messagesArray = [];

        foreach ($messages as $msg) {
            $messagesArray[] = [
                'id'         => Qs::hash($msg->id),
                'body'       => $msg->body,
                'created_at' => $msg->created_at->toIso8601String(),
                'sender'     => $msg->user ? [
                    'id'    => Qs::hash($msg->user->id),
                    'name'  => $msg->user->name,
                    'photo' => $msg->user->photo ? tenant_asset($msg->user->photo) : null,
                ] : null,
            ];
        }

        return $this->sendResponse([
            'thread'   => [
                'id'      => Qs::hash($thread->id),
                'subject' => $thread->subject,
            ],
            'messages' => $messagesArray,
        ], 'Messages retrieved successfully.');
    }

    /**
     * Start a new conversation thread.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject'    => 'required|string|min:3|max:300',
            'message'    => 'required|string|min:2|max:500',
            'recipients' => 'required|array', // Array of hashed user IDs
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $thread = Thread::create([
            'subject' => $request->subject,
        ]);

        // Message
        Message::create([
            'thread_id' => $thread->id,
            'user_id'   => auth()->id(),
            'body'      => $request->message,
        ]);

        // Sender participant
        Participant::create([
            'thread_id' => $thread->id,
            'user_id'   => auth()->id(),
            'last_read' => new Carbon(),
        ]);

        // Recipients
        $recipientIds = [];
        foreach ($request->recipients as $hashedId) {
            $realId = Qs::decodeHash($hashedId);
            if ($realId) {
                $recipientIds[] = (int) $realId;
            }
        }

        if (!empty($recipientIds)) {
            $thread->addParticipant($recipientIds);
        }

        return $this->sendResponse([
            'id'      => Qs::hash($thread->id),
            'subject' => $thread->subject,
        ], 'Message thread created successfully.');
    }

    /**
     * Send a reply in an existing thread.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $realThreadId = Qs::decodeHash($id);
        if (!$realThreadId) {
            return $this->sendError('Invalid Thread ID format.', [], 400);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:2|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        try {
            $thread = Thread::findOrFail($realThreadId);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Thread not found.', [], 404);
        }

        try {
            // Message
            $message = Message::create([
                'thread_id' => $thread->id,
                'user_id'   => auth()->id(),
                'body'      => $request->message,
            ]);

            // Add replier as participant
            $participant = Participant::firstOrCreate([
                'thread_id' => $thread->id,
                'user_id'   => auth()->id(),
            ]);

            $participant->last_read = new Carbon();
            $participant->save();

            // Notify and broadcast
            $message = Message::with(['user', 'deletor'])->find($message->id);
            $users = Participant::where('thread_id', $thread->id)
                ->with('user')
                ->get()
                ->where('user.id', '<>', auth()->id())
                ->whereNotNull('user')
                ->pluck('user');

            if ($message->user && isset($message->user->photo)) {
                $message['user']['photo'] = tenant_asset($message['user']['photo']);
            }

            Notification::sendNow($users, new MessageSent($message));
            broadcast(new NewMessage($message));

            return $this->sendResponse([
                'id'         => Qs::hash($message->id),
                'body'       => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ], 'Reply sent successfully.');
        } catch (Throwable $e) {
            return $this->sendError('System Error.', ['error' => 'Could not send message. Please try again.'], 500);
        }
    }
}
