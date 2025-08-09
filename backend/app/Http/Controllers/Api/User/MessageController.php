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
 * @OA\Tag(
 *     name="User Messaging",
 *     description="Endpoints for messaging and forum communication (requires authentication)"
 * )
 */
class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/user/messages",
     *     tags={"User Messaging"},
     *     summary="Get user messages",
     *     description="Get all messages for the authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by message type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"received", "sent"}, example="received")
     *     ),
     *     @OA\Parameter(
     *         name="batch_id",
     *         in="query",
     *         description="Filter by batch ID",
     *         required=false,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Parameter(
     *         name="is_read",
     *         in="query",
     *         description="Filter by read status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of messages per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User messages retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="message_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="subject", type="string", example="Assignment Deadline Reminder"),
     *                     @OA\Property(property="content", type="string", example="Don't forget to submit your HTML project by Friday."),
     *                     @OA\Property(
     *                         property="sender",
     *                         type="object",
     *                         @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="first_name", type="string", example="John"),
     *                         @OA\Property(property="last_name", type="string", example="Instructor"),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="profile_picture", type="string", example="path/to/image.jpg")
     *                     ),
     *                     @OA\Property(
     *                         property="batch",
     *                         type="object",
     *                         @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A"),
     *                         @OA\Property(property="course_title", type="string", example="Web Development Fundamentals")
     *                     ),
     *                     @OA\Property(property="is_read", type="boolean", example=false),
     *                     @OA\Property(property="sent_at", type="string", format="date-time", example="2024-01-20T14:30:00Z"),
     *                     @OA\Property(property="read_at", type="string", format="date-time", nullable=true, example=null)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/user/messages",
     *     tags={"User Messaging"},
     *     summary="Send a message",
     *     description="Send a message to another user in the same batch",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient_id", "batch_id", "subject", "content"},
     *             @OA\Property(property="recipient_id", type="string", description="Recipient user ID", example="uuid-string"),
     *             @OA\Property(property="batch_id", type="string", description="Batch context for the message", example="uuid-string"),
     *             @OA\Property(property="subject", type="string", description="Message subject", maxLength=255, example="Question about Assignment"),
     *             @OA\Property(property="content", type="string", description="Message content", maxLength=2000, example="I need help with the CSS flexbox exercise.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="subject", type="string", example="Question about Assignment"),
     *                 @OA\Property(property="content", type="string", example="I need help with the CSS flexbox exercise."),
     *                 @OA\Property(
     *                     property="recipient",
     *                     type="object",
     *                     @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="first_name", type="string", example="Jane"),
     *                     @OA\Property(property="last_name", type="string", example="Student"),
     *                     @OA\Property(property="email", type="string", example="jane@example.com")
     *                 ),
     *                 @OA\Property(
     *                     property="batch",
     *                     type="object",
     *                     @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A")
     *                 ),
     *                 @OA\Property(property="sent_at", type="string", format="date-time", example="2024-01-20T16:30:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Message sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot send message",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot send message"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="array",
     *                 @OA\Items(type="string", example="Recipient not in the same batch")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="recipient_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The recipient id field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,user_id',
            'batch_id' => 'required|exists:course_batches,batch_id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string|max:2000'
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
            'content' => $request->content,
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
     * @OA\Patch(
     *     path="/api/user/messages/{message_id}/read",
     *     tags={"User Messaging"},
     *     summary="Mark message as read",
     *     description="Mark a received message as read",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="message_id",
     *         in="path",
     *         description="The message ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found or already read",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message not found or already read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/user/forum/topics",
     *     tags={"User Messaging"},
     *     summary="Get forum topics",
     *     description="Get forum topics for user's enrolled batches",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="batch_id",
     *         in="query",
     *         description="Filter by batch ID",
     *         required=false,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of topics per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Forum topics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="topic_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="title", type="string", example="Discussion: CSS Grid vs Flexbox"),
     *                     @OA\Property(property="description", type="string", example="Let's discuss when to use CSS Grid vs Flexbox"),
     *                     @OA\Property(
     *                         property="author",
     *                         type="object",
     *                         @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="first_name", type="string", example="John"),
     *                         @OA\Property(property="last_name", type="string", example="Instructor"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     ),
     *                     @OA\Property(
     *                         property="batch",
     *                         type="object",
     *                         @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A")
     *                     ),
     *                     @OA\Property(property="replies_count", type="integer", example=12),
     *                     @OA\Property(
     *                         property="latest_reply",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="reply_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="author_name", type="string", example="Jane Student"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-20T15:30:00Z")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-19T10:00:00Z"),
     *                     @OA\Property(property="is_pinned", type="boolean", example=false)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/user/forum/topics",
     *     tags={"User Messaging"},
     *     summary="Create forum topic",
     *     description="Create a new forum topic in a batch",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"batch_id", "title", "description"},
     *             @OA\Property(property="batch_id", type="string", description="Batch ID where topic will be created", example="uuid-string"),
     *             @OA\Property(property="title", type="string", description="Topic title", maxLength=255, example="Need Help with JavaScript Arrays"),
     *             @OA\Property(property="description", type="string", description="Topic description/content", maxLength=2000, example="I'm struggling with array methods. Can someone explain map() vs forEach()?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Forum topic created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="topic_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="title", type="string", example="Need Help with JavaScript Arrays"),
     *                 @OA\Property(property="description", type="string", example="I'm struggling with array methods. Can someone explain map() vs forEach()?"),
     *                 @OA\Property(
     *                     property="author",
     *                     type="object",
     *                     @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="first_name", type="string", example="Jane"),
     *                     @OA\Property(property="last_name", type="string", example="Student")
     *                 ),
     *                 @OA\Property(
     *                     property="batch",
     *                     type="object",
     *                     @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-20T16:45:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Forum topic created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Not enrolled in batch",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are not enrolled in this batch")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="The title field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/user/forum/topics/{topic_id}",
     *     tags={"User Messaging"},
     *     summary="Get forum topic with replies",
     *     description="Get a specific forum topic with all its replies",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="topic_id",
     *         in="path",
     *         description="The topic ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of replies per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Forum topic with replies retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="topic",
     *                     type="object",
     *                     @OA\Property(property="topic_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="title", type="string", example="Discussion: CSS Grid vs Flexbox"),
     *                     @OA\Property(property="description", type="string", example="Let's discuss when to use CSS Grid vs Flexbox"),
     *                     @OA\Property(
     *                         property="author",
     *                         type="object",
     *                         @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="first_name", type="string", example="John"),
     *                         @OA\Property(property="last_name", type="string", example="Instructor"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-19T10:00:00Z")
     *                 ),
     *                 @OA\Property(
     *                     property="replies",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="reply_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="reply_text", type="string", example="I think CSS Grid is better for 2D layouts..."),
     *                         @OA\Property(
     *                             property="author",
     *                             type="object",
     *                             @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                             @OA\Property(property="first_name", type="string", example="Jane"),
     *                             @OA\Property(property="last_name", type="string", example="Student"),
     *                             @OA\Property(property="profile_picture", type="string", example="path/to/image.jpg")
     *                         ),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-19T14:30:00Z")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="pagination",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=12),
     *                     @OA\Property(property="last_page", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Topic not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\BatchForumTopic]")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/user/forum/topics/{topic_id}/replies",
     *     tags={"User Messaging"},
     *     summary="Reply to forum topic",
     *     description="Add a reply to a forum topic",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="topic_id",
     *         in="path",
     *         description="The topic ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reply_text"},
     *             @OA\Property(property="reply_text", type="string", description="Reply content", maxLength=2000, example="Great explanation! I agree that Grid is perfect for complex layouts.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reply added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reply_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="reply_text", type="string", example="Great explanation! I agree that Grid is perfect for complex layouts."),
     *                 @OA\Property(
     *                     property="author",
     *                     type="object",
     *                     @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="first_name", type="string", example="Mike"),
     *                     @OA\Property(property="last_name", type="string", example="Student")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-20T17:00:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Reply added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Topic not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\BatchForumTopic]")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="reply_text",
     *                     type="array",
     *                     @OA\Items(type="string", example="The reply text field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
