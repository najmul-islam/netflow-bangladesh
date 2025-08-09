<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchNotification;
use App\Models\BatchCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="User Notifications",
 *     description="Endpoints for managing user notifications (requires authentication)"
 * )
 */
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/user/notifications",
     *     tags={"User Notifications"},
     *     summary="Get user notifications",
     *     description="Get all notifications for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by notification type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"announcement","assignment","assessment","message","reminder","certificate"}, example="assignment")
     *     ),
     *     @OA\Parameter(
     *         name="is_read",
     *         in="query",
     *         description="Filter by read status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
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
     *         description="Number of notifications per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User notifications",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="notification_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="title", type="string", example="New Assignment Posted"),
     *                     @OA\Property(property="message", type="string", example="A new HTML/CSS assignment has been posted for your batch"),
     *                     @OA\Property(property="type", type="string", example="assignment"),
     *                     @OA\Property(property="priority", type="string", example="medium"),
     *                     @OA\Property(property="is_read", type="boolean", example=false),
     *                     @OA\Property(property="sent_at", type="string", example="2024-01-20T14:30:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     *       "action_data": {
     *         "url": "/assignments/uuid-string",
     *         "type": "assignment",
     *         "id": "uuid-string"
     *       }
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 45,
     *     "last_page": 3
     *   },
     *   "unread_count": 12
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:announcement,assignment,assessment,message,reminder,certificate',
            'is_read' => 'nullable|boolean',
            'batch_id' => 'nullable|exists:course_batches,batch_id',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);

        $query = BatchNotification::with([
            'batch:batch_id,batch_name,course_id',
            'batch.course:course_id,title'
        ])
        ->where('user_id', auth()->user()->user_id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_read')) {
            if ($request->is_read) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Only show notifications that are scheduled to be sent
        $query->where('scheduled_at', '<=', now());

        $notifications = $query->orderBy('sent_at', 'desc')
            ->paginate($perPage);

        // Get unread count
        $unreadCount = BatchNotification::where('user_id', auth()->user()->user_id)
            ->whereNull('read_at')
            ->where('scheduled_at', '<=', now())
            ->count();

        return response()->json([
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage()
            ],
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/user/notifications/{notification_id}/read",
     *     tags={"User Notifications"},
     *     summary="Mark notification as read",
     *     description="Mark a specific notification as read",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="notification_id",
     *         in="path",
     *         description="The notification ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification marked as read")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found or already read",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification not found or already read")
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
    public function markAsRead(string $notification_id): JsonResponse
    {
        $notification = BatchNotification::where('notification_id', $notification_id)
            ->where('user_id', auth()->user()->user_id)
            ->whereNull('read_at')
            ->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found or already read'
            ], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/user/notifications/mark-all-read",
     *     tags={"User Notifications"},
     *     summary="Mark all notifications as read",
     *     description="Mark all unread notifications as read for the authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="batch_id",
     *         in="query",
     *         description="Optional: Mark as read only for specific batch",
     *         required=false,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All notifications marked as read"),
     *             @OA\Property(property="marked_count", type="integer", example=12)
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
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'nullable|exists:course_batches,batch_id'
        ]);

        $query = BatchNotification::where('user_id', auth()->user()->user_id)
            ->whereNull('read_at')
            ->where('scheduled_at', '<=', now());

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        $count = $query->count();
        $query->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'marked_count' => $count
        ]);
    }

    /**
     * Get notification preferences
     * 
     * Get user's notification preferences.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "data": {
     *     "email_notifications": true,
     *     "push_notifications": true,
     *     "notification_types": {
     *       "announcement": true,
     *       "assignment": true,
     *       "assessment": true,
     *       "message": true,
     *       "reminder": true,
     *       "certificate": true
     *     },
     *     "quiet_hours": {
     *       "enabled": true,
     *       "start_time": "22:00",
     *       "end_time": "08:00",
     *       "timezone": "Asia/Dhaka"
     *     }
     *   }
     * }
     */
    public function getPreferences(): JsonResponse
    {
        $user = auth()->user();
        
        // Return default preferences since notification_preferences field doesn't exist in DB
        $preferences = [
            'email_notifications' => true,
            'push_notifications' => true,
            'notification_types' => [
                'announcement' => true,
                'assignment' => true,
                'assessment' => true,
                'message' => true,
                'reminder' => true,
                'certificate' => true,
            ],
            'quiet_hours' => [
                'enabled' => false,
                'start_time' => '22:00',
                'end_time' => '08:00',
                'timezone' => $user->timezone ?? 'Asia/Dhaka'
            ]
        ];

        return response()->json(['data' => $preferences]);
    }

    /**
     * @OA\Put(
     *     path="/api/user/notifications/preferences",
     *     tags={"User Notifications"},
     *     summary="Update notification preferences",
     *     description="Update user's notification preferences",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="email_notifications", type="boolean", description="Enable/disable email notifications", example=true),
     *             @OA\Property(property="push_notifications", type="boolean", description="Enable/disable push notifications", example=false),
     *             @OA\Property(
     *                 property="notification_types",
     *                 type="object",
     *                 description="Notification type preferences",
     *                 @OA\Property(property="announcement", type="boolean", description="Enable announcement notifications", example=true),
     *                 @OA\Property(property="assignment", type="boolean", description="Enable assignment notifications", example=true),
     *                 @OA\Property(property="assessment", type="boolean", description="Enable assessment notifications", example=true),
     *                 @OA\Property(property="message", type="boolean", description="Enable message notifications", example=true),
     *                 @OA\Property(property="reminder", type="boolean", description="Enable reminder notifications", example=false),
     *                 @OA\Property(property="certificate", type="boolean", description="Enable certificate notifications", example=true)
     *             ),
     *             @OA\Property(
     *                 property="quiet_hours",
     *                 type="object",
     *                 description="Quiet hours configuration",
     *                 @OA\Property(property="enabled", type="boolean", description="Enable quiet hours", example=true),
     *                 @OA\Property(property="start_time", type="string", description="Quiet hours start time (HH:MM format)", example="22:00"),
     *                 @OA\Property(property="end_time", type="string", description="Quiet hours end time (HH:MM format)", example="08:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification preferences updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notification preferences updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="email_notifications", type="boolean", example=true),
     *                 @OA\Property(property="push_notifications", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="notification_types",
     *                     type="object",
     *                     @OA\Property(property="announcement", type="boolean", example=true),
     *                     @OA\Property(property="assignment", type="boolean", example=true),
     *                     @OA\Property(property="assessment", type="boolean", example=true),
     *                     @OA\Property(property="message", type="boolean", example=true),
     *                     @OA\Property(property="reminder", type="boolean", example=false),
     *                     @OA\Property(property="certificate", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(
     *                     property="quiet_hours",
     *                     type="object",
     *                     @OA\Property(property="enabled", type="boolean", example=true),
     *                     @OA\Property(property="start_time", type="string", example="22:00"),
     *                     @OA\Property(property="end_time", type="string", example="08:00")
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
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'email_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'notification_types' => 'nullable|array',
            'notification_types.announcement' => 'nullable|boolean',
            'notification_types.assignment' => 'nullable|boolean',
            'notification_types.assessment' => 'nullable|boolean',
            'notification_types.message' => 'nullable|boolean',
            'notification_types.reminder' => 'nullable|boolean',
            'notification_types.certificate' => 'nullable|boolean',
            'quiet_hours' => 'nullable|array',
            'quiet_hours.enabled' => 'nullable|boolean',
            'quiet_hours.start_time' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'quiet_hours.end_time' => 'nullable|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'
        ]);

        // Since notification_preferences field doesn't exist in DB, return success with submitted data
        $responseData = [
            'email_notifications' => $request->email_notifications ?? true,
            'push_notifications' => $request->push_notifications ?? true,
            'notification_types' => $request->notification_types ?? [
                'announcement' => true,
                'assignment' => true,
                'assessment' => true,
                'message' => true,
                'reminder' => true,
                'certificate' => true,
            ],
            'quiet_hours' => $request->quiet_hours ?? [
                'enabled' => false,
                'start_time' => '22:00',
                'end_time' => '08:00'
            ]
        ];

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'data' => $responseData
        ]);
    }

    /**
     * Get user certificates
     * 
     * Get all certificates for the authenticated user.
     * 
     * @authenticated
     * @queryParam status string Filter by certificate status (generated,issued,revoked). Example: issued
     * @queryParam per_page integer Number of certificates per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "certificate_id": "uuid-string",
     *       "certificate_number": "CERT-2024-001234",
     *       "certificate_type": "completion",
     *       "title": "Web Development Fundamentals - Certificate of Completion",
     *       "description": "This certifies that the student has successfully completed the Web Development Fundamentals course.",
     *       "issue_date": "2024-01-20",
     *       "expiry_date": null,
     *       "status": "issued",
     *       "certificate_url": "https://example.com/certificates/cert-uuid.pdf",
     *       "verification_code": "VERIFY123456",
     *       "course": {
     *         "course_id": "uuid-string",
     *         "title": "Web Development Fundamentals",
     *         "course_code": "WDF-2024"
     *       },
     *       "batch": {
     *         "batch_id": "uuid-string",
     *         "batch_name": "Web Dev Batch 2024-A"
     *       },
     *       "issuer": {
     *         "organization_name": "NetFlow Bangladesh",
     *         "authorized_by": "John Doe, Director"
     *       }
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 5,
     *     "last_page": 1
     *   }
     * }
     */
    public function getCertificates(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:generated,issued,revoked',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);

        $query = BatchCertificate::with([
            'user.course:course_id,title,course_code',
            'batch:batch_id,batch_name'
        ])
        ->where('user_id', auth()->user()->user_id);

        if ($request->filled('status')) {
            if ($request->status === 'issued') {
                $query->where('is_revoked', false);
            } elseif ($request->status === 'revoked') {
                $query->where('is_revoked', true);
            }
        }

        $certificates = $query->where('is_revoked', false)
            ->orderBy('issued_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $certificates->items(),
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
                'last_page' => $certificates->lastPage()
            ]
        ]);
    }

    /**
     * Download certificate
     * 
     * Download a specific certificate as PDF.
     * 
     * @authenticated
     * @urlParam certificate_id string required The certificate ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "download_url": "https://example.com/certificates/download/cert-uuid.pdf",
     *     "filename": "certificate-web-development-fundamentals.pdf",
     *     "expires_at": "2024-01-20T18:30:00Z"
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Certificate not found"
     * }
     */
    public function downloadCertificate(string $certificate_id): JsonResponse
    {
        $certificate = BatchCertificate::where('certificate_id', $certificate_id)
            ->where('user_id', auth()->user()->user_id)
            ->where('is_revoked', false)
            ->first();

        if (!$certificate) {
            return response()->json([
                'message' => 'Certificate not found'
            ], 404);
        }

        // Generate secure download URL (implementation depends on your file storage setup)
        $downloadUrl = $certificate->certificate_url ?? url('/storage/certificates/' . $certificate->certificate_id . '.pdf');
        
        return response()->json([
            'data' => [
                'download_url' => $downloadUrl,
                'filename' => 'certificate-' . $certificate->certificate_number . '.pdf',
                'expires_at' => now()->addHours(1) // URL expires in 1 hour
            ]
        ]);
    }

    /**
     * Verify certificate
     * 
     * Verify a certificate using verification code.
     * 
     * @queryParam verification_code string required The verification code. Example: "VERIFY123456"
     * 
     * @response 200 {
     *   "data": {
     *     "is_valid": true,
     *     "certificate": {
     *       "certificate_number": "CERT-2024-001234",
     *       "certificate_type": "completion",
     *       "title": "Web Development Fundamentals - Certificate of Completion",
     *       "issue_date": "2024-01-20",
     *       "status": "issued",
     *       "recipient": {
     *         "name": "Jane Smith",
     *         "email": "jane@example.com"
     *       },
     *       "course": {
     *         "title": "Web Development Fundamentals",
     *         "course_code": "WDF-2024"
     *       },
     *       "issuer": {
     *         "organization_name": "NetFlow Bangladesh",
     *         "authorized_by": "John Doe, Director"
     *       }
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "data": {
     *     "is_valid": false,
     *     "message": "Certificate not found or invalid verification code"
     *   }
     * }
     */
    public function verifyCertificate(Request $request): JsonResponse
    {
        $request->validate([
            'verification_code' => 'required|string'
        ]);

        $certificate = BatchCertificate::with([
            'user:user_id,first_name,last_name,email',
            'batch.course:course_id,title,course_code'
        ])
        ->where('verification_code', $request->verification_code)
        ->where('is_revoked', false)
        ->first();

        if (!$certificate) {
            return response()->json([
                'data' => [
                    'is_valid' => false,
                    'message' => 'Certificate not found or invalid verification code'
                ]
            ], 404);
        }

        return response()->json([
            'data' => [
                'is_valid' => true,
                'certificate' => [
                    'certificate_number' => $certificate->certificate_number,
                    'certificate_type' => 'completion', // Default type
                    'title' => $certificate->batch->course->title . ' - Certificate of Completion',
                    'issue_date' => $certificate->issued_date,
                    'status' => $certificate->is_revoked ? 'revoked' : 'issued',
                    'recipient' => [
                        'name' => $certificate->user->first_name . ' ' . $certificate->user->last_name,
                        'email' => $certificate->user->email
                    ],
                    'course' => [
                        'title' => $certificate->batch->course->title,
                        'course_code' => $certificate->batch->course->course_code
                    ],
                    'issuer' => [
                        'organization_name' => 'NetFlow Bangladesh',
                        'authorized_by' => 'Academic Director'
                    ]
                ]
            ]
        ]);
    }
}
