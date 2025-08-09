<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchMessage;
use App\Models\BatchForumTopic;
use App\Models\BatchForumReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group User API - Messaging
 * 
 * Endpoints for messaging and forum communication (requires authentication)
 */
class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user messages
     * 
     * Get all messages for the authenticated user.
     * 
     * @authenticated
     * @queryParam type string Filter by message type (received,sent). Example: received
     * @queryParam batch_id string Filter by batch ID. Example: "uuid-string"
     * @queryParam is_read boolean Filter by read status. Example: false
     * @queryParam per_page integer Number of messages per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "message_id": "uuid-string",
     *       "subject": "Assignment Deadline Reminder",
     *       "message_body": "Don't forget to submit your HTML project by Friday.",
     *       "sender": {
     *         "user_id": "uuid-string",
     *         "first_name": "John",
     *         "last_name": "Instructor",
     *         "email": "john@example.com",
     *         "profile_picture": "path/to/image.jpg"
     *       },
     *       "batch": {
     *         "batch_id": "uuid-string",
     *         "batch_name": "Web Dev Batch 2024-A",
     *         "course_title": "Web Development Fundamentals"
     *       },
     *       "is_read": false,
     *       "sent_at": "2024-01-20T14:30:00Z",
     *       "read_at": null
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 45,
     *     "last_page": 3
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:received,sent',
            'batch_id' => 'nullable|exists:course_batches,batch_id',
            'is_read' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);
        $type = $request->get('type', 'received');

        $query = BatchMessage::with([
            'sender:user_id,first_name,last_name,email,profile_picture',
            'recipient:user_id,first_name,last_name,email,profile_picture',
            'batch:batch_id,batch_name,course_id',
            'batch.course:course_id,title'
        ]);

        if ($type === 'received') {
            $query->where('recipient_id', auth()->user()->user_id);
        } else {
            $query->where('sender_id', auth()->user()->user_id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('is_read')) {
            if ($request->is_read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $messages = $query->orderBy('sent_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage()
            ]
        ]);
    }

    /**
     * Send message
     * 
     * Send a message to another user in the same batch.
     * 
     * @authenticated
     * @bodyParam recipient_id string required Recipient user ID.
     * @bodyParam batch_id string required Batch context for the message.
     * @bodyParam subject string required Message subject.
     * @bodyParam message_body string required Message content.
     * 
     * @response 201 {
     *   "data": {
     *     "message_id": "uuid-string",
     *     "subject": "Question about Assignment",
     *     "message_body": "I need help with the CSS flexbox exercise.",
     *     "recipient": {
     *       "user_id": "uuid-string",
     *       "first_name": "Jane",
     *       "last_name": "Student",
     *       "email": "jane@example.com"
     *     },
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A"
     *     },
     *     "sent_at": "2024-01-20T16:30:00Z"
     *   },
     *   "message": "Message sent successfully"
     * }
     * 
     * @response 403 {
     *   "message": "Cannot send message",
     *   "errors": ["Recipient not in the same batch"]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,user_id',
            'batch_id' => 'required|exists:course_batches,batch_id',
            'subject' => 'required|string|max:255',
            'message_body' => 'required|string|max:2000'
        ]);

        // Verify sender is enrolled in the batch
        $senderEnrollment = auth()->user()->enrollments()
            ->where('batch_id', $request->batch_id)
            ->where('status', 'active')
            ->first();

        if (!$senderEnrollment) {
            return response()->json([
                'message' => 'Cannot send message',
                'errors' => ['You are not enrolled in this batch']
            ], 403);
        }

        // Verify recipient is also in the same batch
        $recipientEnrollment = DB::table('batch_enrollments')
            ->where('user_id', $request->recipient_id)
            ->where('batch_id', $request->batch_id)
            ->where('status', 'active')
            ->exists();

        if (!$recipientEnrollment) {
            return response()->json([
                'message' => 'Cannot send message',
                'errors' => ['Recipient not in the same batch']
            ], 403);
        }

        $message = BatchMessage::create([
            'sender_id' => auth()->user()->user_id,
            'recipient_id' => $request->recipient_id,
            'batch_id' => $request->batch_id,
            'subject' => $request->subject,
            'message_body' => $request->message_body,
            'sent_at' => now()
        ]);

        $message->load([
            'recipient:user_id,first_name,last_name,email',
            'batch:batch_id,batch_name'
        ]);

        return response()->json([
            'data' => $message,
            'message' => 'Message sent successfully'
        ], 201);
    }

    /**
     * Mark message as read
     * 
     * Mark a received message as read.
     * 
     * @authenticated
     * @urlParam message_id string required The message ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "message": "Message marked as read"
     * }
     */
    public function markAsRead(string $message_id): JsonResponse
    {
        $message = BatchMessage::where('message_id', $message_id)
            ->where('recipient_id', auth()->user()->user_id)
            ->whereNull('read_at')
            ->first();

        if (!$message) {
            return response()->json([
                'message' => 'Message not found or already read'
            ], 404);
        }

        $message->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Message marked as read'
        ]);
    }

    /**
     * Get forum topics
     * 
     * Get forum topics for user's enrolled batches.
     * 
     * @authenticated
     * @queryParam batch_id string Filter by batch ID. Example: "uuid-string"
     * @queryParam per_page integer Number of topics per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "topic_id": "uuid-string",
     *       "title": "Discussion: CSS Grid vs Flexbox",
     *       "description": "Let's discuss when to use CSS Grid vs Flexbox",
     *       "author": {
     *         "user_id": "uuid-string",
     *         "first_name": "John",
     *         "last_name": "Instructor",
     *         "email": "john@example.com"
     *       },
     *       "batch": {
     *         "batch_id": "uuid-string",
     *         "batch_name": "Web Dev Batch 2024-A"
     *       },
     *       "replies_count": 12,
     *       "latest_reply": {
     *         "reply_id": "uuid-string",
     *         "author_name": "Jane Student",
     *         "created_at": "2024-01-20T15:30:00Z"
     *       },
     *       "created_at": "2024-01-19T10:00:00Z",
     *       "is_pinned": false
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 25,
     *     "last_page": 2
     *   }
     * }
     */
    public function getForumTopics(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'nullable|exists:course_batches,batch_id',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);

        // Get user's enrolled batch IDs
        $userBatchIds = auth()->user()->enrollments()
            ->where('status', 'active')
            ->pluck('batch_id');

        $query = BatchForumTopic::with([
            'author:user_id,first_name,last_name,email',
            'batch:batch_id,batch_name'
        ])
        ->withCount('replies')
        ->whereIn('batch_id', $userBatchIds);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        $topics = $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Add latest reply info
        $topics->getCollection()->each(function ($topic) {
            $latestReply = BatchForumReply::with('author:user_id,first_name,last_name')
                ->where('topic_id', $topic->topic_id)
                ->orderBy('created_at', 'desc')
                ->first();

            $topic->latest_reply = $latestReply ? [
                'reply_id' => $latestReply->reply_id,
                'author_name' => $latestReply->author->first_name . ' ' . $latestReply->author->last_name,
                'created_at' => $latestReply->created_at
            ] : null;
        });

        return response()->json([
            'data' => $topics->items(),
            'pagination' => [
                'current_page' => $topics->currentPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
                'last_page' => $topics->lastPage()
            ]
        ]);
    }

    /**
     * Create forum topic
     * 
     * Create a new forum topic in a batch.
     * 
     * @authenticated
     * @bodyParam batch_id string required Batch ID where topic will be created.
     * @bodyParam title string required Topic title.
     * @bodyParam description string required Topic description/content.
     * 
     * @response 201 {
     *   "data": {
     *     "topic_id": "uuid-string",
     *     "title": "Need Help with JavaScript Arrays",
     *     "description": "I'm struggling with array methods. Can someone explain map() vs forEach()?",
     *     "author": {
     *       "user_id": "uuid-string",
     *       "first_name": "Jane",
     *       "last_name": "Student"
     *     },
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A"
     *     },
     *     "created_at": "2024-01-20T16:45:00Z"
     *   },
     *   "message": "Forum topic created successfully"
     * }
     */
    public function createForumTopic(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:course_batches,batch_id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000'
        ]);

        // Verify user is enrolled in the batch
        $enrollment = auth()->user()->enrollments()
            ->where('batch_id', $request->batch_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'You are not enrolled in this batch'
            ], 403);
        }

        $topic = BatchForumTopic::create([
            'batch_id' => $request->batch_id,
            'author_id' => auth()->user()->user_id,
            'title' => $request->title,
            'description' => $request->description,
            'is_pinned' => false
        ]);

        $topic->load([
            'author:user_id,first_name,last_name',
            'batch:batch_id,batch_name'
        ]);

        return response()->json([
            'data' => $topic,
            'message' => 'Forum topic created successfully'
        ], 201);
    }

    /**
     * Get forum topic with replies
     * 
     * Get a specific forum topic with all its replies.
     * 
     * @authenticated
     * @urlParam topic_id string required The topic ID. Example: "uuid-string"
     * @queryParam per_page integer Number of replies per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": {
     *     "topic": {
     *       "topic_id": "uuid-string",
     *       "title": "Discussion: CSS Grid vs Flexbox",
     *       "description": "Let's discuss when to use CSS Grid vs Flexbox",
     *       "author": {
     *         "user_id": "uuid-string",
     *         "first_name": "John",
     *         "last_name": "Instructor",
     *         "email": "john@example.com"
     *       },
     *       "created_at": "2024-01-19T10:00:00Z"
     *     },
     *     "replies": [
     *       {
     *         "reply_id": "uuid-string",
     *         "reply_text": "I think CSS Grid is better for 2D layouts...",
     *         "author": {
     *           "user_id": "uuid-string",
     *           "first_name": "Jane",
     *           "last_name": "Student",
     *           "profile_picture": "path/to/image.jpg"
     *         },
     *         "created_at": "2024-01-19T14:30:00Z"
     *       }
     *     ],
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 20,
     *       "total": 12,
     *       "last_page": 1
     *     }
     *   }
     * }
     */
    public function getForumTopic(Request $request, string $topic_id): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);

        $topic = BatchForumTopic::with([
            'author:user_id,first_name,last_name,email',
            'batch:batch_id,batch_name'
        ])
        ->where('topic_id', $topic_id)
        ->firstOrFail();

        // Verify user is enrolled in the batch
        $enrollment = auth()->user()->enrollments()
            ->where('batch_id', $topic->batch_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'Access denied'
            ], 403);
        }

        $replies = BatchForumReply::with([
            'author:user_id,first_name,last_name,profile_picture'
        ])
        ->where('topic_id', $topic_id)
        ->orderBy('created_at')
        ->paginate($perPage);

        return response()->json([
            'data' => [
                'topic' => $topic,
                'replies' => $replies->items(),
                'pagination' => [
                    'current_page' => $replies->currentPage(),
                    'per_page' => $replies->perPage(),
                    'total' => $replies->total(),
                    'last_page' => $replies->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Reply to forum topic
     * 
     * Add a reply to a forum topic.
     * 
     * @authenticated
     * @urlParam topic_id string required The topic ID. Example: "uuid-string"
     * @bodyParam reply_text string required Reply content.
     * 
     * @response 201 {
     *   "data": {
     *     "reply_id": "uuid-string",
     *     "reply_text": "Great explanation! I agree that Grid is perfect for complex layouts.",
     *     "author": {
     *       "user_id": "uuid-string",
     *       "first_name": "Mike",
     *       "last_name": "Student"
     *     },
     *     "created_at": "2024-01-20T17:00:00Z"
     *   },
     *   "message": "Reply added successfully"
     * }
     */
    public function replyToTopic(Request $request, string $topic_id): JsonResponse
    {
        $request->validate([
            'reply_text' => 'required|string|max:2000'
        ]);

        $topic = BatchForumTopic::where('topic_id', $topic_id)->firstOrFail();

        // Verify user is enrolled in the batch
        $enrollment = auth()->user()->enrollments()
            ->where('batch_id', $topic->batch_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'Access denied'
            ], 403);
        }

        $reply = BatchForumReply::create([
            'topic_id' => $topic_id,
            'author_id' => auth()->user()->user_id,
            'reply_text' => $request->reply_text
        ]);

        $reply->load('author:user_id,first_name,last_name');

        return response()->json([
            'data' => $reply,
            'message' => 'Reply added successfully'
        ], 201);
    }
}
