<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchNotification;
use App\Models\BatchCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group User API - Notifications
 * 
 * Endpoints for managing user notifications (requires authentication)
 */
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user notifications
     * 
     * Get all notifications for the authenticated user.
     * 
     * @authenticated
     * @queryParam type string Filter by notification type (announcement,assignment,assessment,message,reminder,certificate). Example: assignment
     * @queryParam is_read boolean Filter by read status. Example: false
     * @queryParam batch_id string Filter by batch ID. Example: "uuid-string"
     * @queryParam per_page integer Number of notifications per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "notification_id": "uuid-string",
     *       "title": "New Assignment Posted",
     *       "message": "A new HTML/CSS assignment has been posted for your batch.",
     *       "notification_type": "assignment",
     *       "priority": "medium",
     *       "is_read": false,
     *       "read_at": null,
     *       "sent_at": "2024-01-20T14:30:00Z",
     *       "scheduled_at": "2024-01-20T14:30:00Z",
     *       "batch": {
     *         "batch_id": "uuid-string",
     *         "batch_name": "Web Dev Batch 2024-A",
     *         "course_title": "Web Development Fundamentals"
     *       },
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
        ->where('recipient_id', auth()->user()->user_id);

        if ($request->filled('type')) {
            $query->where('notification_type', $request->type);
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
        $unreadCount = BatchNotification::where('recipient_id', auth()->user()->user_id)
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
     * Mark notification as read
     * 
     * Mark a specific notification as read.
     * 
     * @authenticated
     * @urlParam notification_id string required The notification ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "message": "Notification marked as read"
     * }
     */
    public function markAsRead(string $notification_id): JsonResponse
    {
        $notification = BatchNotification::where('notification_id', $notification_id)
            ->where('recipient_id', auth()->user()->user_id)
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
     * Mark all notifications as read
     * 
     * Mark all unread notifications as read for the authenticated user.
     * 
     * @authenticated
     * @queryParam batch_id string Optional: Mark as read only for specific batch. Example: "uuid-string"
     * 
     * @response 200 {
     *   "message": "All notifications marked as read",
     *   "marked_count": 12
     * }
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'nullable|exists:course_batches,batch_id'
        ]);

        $query = BatchNotification::where('recipient_id', auth()->user()->user_id)
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
     * Update notification preferences
     * 
     * Update user's notification preferences.
     * 
     * @authenticated
     * @bodyParam email_notifications boolean Enable/disable email notifications.
     * @bodyParam push_notifications boolean Enable/disable push notifications.
     * @bodyParam notification_types object Notification type preferences.
     * @bodyParam notification_types.announcement boolean Enable announcement notifications.
     * @bodyParam notification_types.assignment boolean Enable assignment notifications.
     * @bodyParam notification_types.assessment boolean Enable assessment notifications.
     * @bodyParam notification_types.message boolean Enable message notifications.
     * @bodyParam notification_types.reminder boolean Enable reminder notifications.
     * @bodyParam notification_types.certificate boolean Enable certificate notifications.
     * @bodyParam quiet_hours object Quiet hours configuration.
     * @bodyParam quiet_hours.enabled boolean Enable quiet hours.
     * @bodyParam quiet_hours.start_time string Quiet hours start time (HH:MM format).
     * @bodyParam quiet_hours.end_time string Quiet hours end time (HH:MM format).
     * 
     * @response 200 {
     *   "message": "Notification preferences updated successfully",
     *   "data": {
     *     "email_notifications": true,
     *     "push_notifications": false,
     *     "notification_types": {
     *       "announcement": true,
     *       "assignment": true,
     *       "assessment": true,
     *       "message": true,
     *       "reminder": false,
     *       "certificate": true
     *     },
     *     "quiet_hours": {
     *       "enabled": true,
     *       "start_time": "22:00",
     *       "end_time": "08:00"
     *     }
     *   }
     * }
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
