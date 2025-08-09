<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchEnrollment;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group User API - Enrollments
 * 
 * Endpoints for managing course enrollments (requires authentication)
 */
class EnrollmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user enrollments
     * 
     * Get all enrollments for the authenticated user.
     * 
     * @authenticated
     * @queryParam status string Filter by enrollment status (pending,active,completed,dropped,suspended). Example: active
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 20). Example: 10
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "enrollment_id": "uuid-string",
     *       "enrollment_date": "2024-01-15",
     *       "status": "active",
     *       "progress_percentage": 65.5,
     *       "completion_date": null,
     *       "batch": {
     *         "batch_id": "uuid-string",
     *         "batch_name": "Web Dev Batch 2024-A",
     *         "start_date": "2024-02-01",
     *         "end_date": "2024-04-01",
     *         "status": "active",
     *         "course": {
     *           "course_id": "uuid-string",
     *           "title": "Web Development Fundamentals",
     *           "thumbnail_url": "https://example.com/thumb.jpg",
     *           "level": "beginner"
     *         }
     *       },
     *       "latest_activity": {
     *         "last_lesson_date": "2024-01-20",
     *         "last_lesson_title": "JavaScript Variables"
     *       }
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 3,
     *     "per_page": 10,
     *     "last_page": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth()->user()->enrollments()
            ->with([
                'batch.course:course_id,title,thumbnail_url,level',
                'batch:batch_id,course_id,batch_name,start_date,end_date,status'
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('enrollment_date', 'desc');

        $perPage = min($request->get('per_page', 10), 20);
        $enrollments = $query->paginate($perPage);

        // Add progress and latest activity data
        $enrollments->getCollection()->transform(function ($enrollment) {
            // Calculate progress percentage
            $totalLessons = $enrollment->batch->course->modules()
                ->withCount('lessons')
                ->get()
                ->sum('lessons_count');

            $completedLessons = $enrollment->lessonProgress()
                ->where('status', 'completed')
                ->count();

            $enrollment->progress_percentage = $totalLessons > 0 
                ? round(($completedLessons / $totalLessons) * 100, 1) 
                : 0;

            // Get latest activity
            $latestProgress = $enrollment->lessonProgress()
                ->with('lesson:lesson_id,title')
                ->latest('updated_at')
                ->first();

            $enrollment->latest_activity = $latestProgress ? [
                'last_lesson_date' => $latestProgress->updated_at->format('Y-m-d'),
                'last_lesson_title' => $latestProgress->lesson->title
            ] : null;

            return $enrollment;
        });

        return response()->json([
            'data' => $enrollments->items(),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'total' => $enrollments->total(),
                'per_page' => $enrollments->perPage(),
                'last_page' => $enrollments->lastPage(),
            ]
        ]);
    }

    /**
     * Enroll in a batch
     * 
     * Enroll the authenticated user in a course batch.
     * 
     * @authenticated
     * @bodyParam batch_id string required The batch ID to enroll in. Example: "uuid-string"
     * 
     * @response 201 {
     *   "data": {
     *     "enrollment_id": "uuid-string",
     *     "user_id": "uuid-string",
     *     "batch_id": "uuid-string",
     *     "enrollment_date": "2024-01-15",
     *     "status": "pending",
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "price": 99.99,
     *       "currency": "USD"
     *     }
     *   },
     *   "message": "Successfully enrolled in the batch"
     * }
     * 
     * @response 400 {
     *   "message": "Enrollment not available",
     *   "errors": ["Batch is full", "Enrollment period has ended"]
     * }
     * 
     * @response 409 {
     *   "message": "Already enrolled in this batch"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:course_batches,batch_id'
        ]);

        $batch = CourseBatch::with('course')
            ->withCount('enrollments as enrolled_count')
            ->findOrFail($request->batch_id);

        // Check if already enrolled
        $existingEnrollment = auth()->user()->enrollments()
            ->where('batch_id', $request->batch_id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Already enrolled in this batch'
            ], 409);
        }

        // Validate enrollment availability
        $errors = [];

        if ($batch->status !== 'open') {
            $errors[] = 'Batch is not open for enrollment';
        }

        if (now() < $batch->enrollment_start) {
            $errors[] = 'Enrollment has not started yet';
        }

        if (now() > $batch->enrollment_end) {
            $errors[] = 'Enrollment period has ended';
        }

        if ($batch->enrolled_count >= $batch->max_students) {
            $errors[] = 'Batch is full';
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Enrollment not available',
                'errors' => $errors
            ], 400);
        }

        // Create enrollment
        $enrollment = BatchEnrollment::create([
            'user_id' => auth()->user()->user_id,
            'batch_id' => $request->batch_id,
            'enrollment_date' => now(),
            'status' => 'pending'
        ]);

        $enrollment->load([
            'batch:batch_id,batch_name,price,currency'
        ]);

        return response()->json([
            'data' => $enrollment,
            'message' => 'Successfully enrolled in the batch'
        ], 201);
    }

    /**
     * Get enrollment details
     * 
     * Get detailed information about a specific enrollment.
     * 
     * @authenticated
     * @urlParam enrollment_id string required The enrollment ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "enrollment_id": "uuid-string",
     *     "enrollment_date": "2024-01-15",
     *     "status": "active",
     *     "progress_percentage": 65.5,
     *     "completion_date": null,
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "start_date": "2024-02-01",
     *       "end_date": "2024-04-01",
     *       "status": "active",
     *       "course": {
     *         "course_id": "uuid-string",
     *         "title": "Web Development Fundamentals",
     *         "description": "Learn web development from basics",
     *         "level": "beginner",
     *         "duration_hours": 40
     *       }
     *     },
     *     "progress_details": {
     *       "total_modules": 6,
     *       "completed_modules": 4,
     *       "total_lessons": 24,
     *       "completed_lessons": 16,
     *       "total_assessments": 3,
     *       "completed_assessments": 2,
     *       "average_score": 85.5
     *     }
     *   }
     * }
     */
    public function show(string $enrollment_id): JsonResponse
    {
        $enrollment = auth()->user()->enrollments()
            ->with([
                'batch.course:course_id,title,description,level,duration_hours',
                'batch:batch_id,course_id,batch_name,start_date,end_date,status'
            ])
            ->where('enrollment_id', $enrollment_id)
            ->firstOrFail();

        // Calculate detailed progress
        $course = $enrollment->batch->course;
        
        $totalModules = $course->modules()->count();
        $totalLessons = $course->modules()->withCount('lessons')->get()->sum('lessons_count');
        
        $completedLessons = $enrollment->lessonProgress()
            ->where('status', 'completed')
            ->count();

        $completedModules = $course->modules()
            ->whereDoesntHave('lessons', function ($query) use ($enrollment) {
                $query->whereDoesntHave('progress', function ($subQuery) use ($enrollment) {
                    $subQuery->where('user_id', $enrollment->user_id)
                             ->where('status', 'completed');
                });
            })
            ->count();

        $assessmentAttempts = $enrollment->assessmentAttempts()
            ->with('assessment')
            ->get();

        $totalAssessments = $enrollment->batch->assessments()->count();
        $completedAssessments = $assessmentAttempts->where('status', 'completed')->count();
        $averageScore = $assessmentAttempts->where('status', 'completed')->avg('score') ?? 0;

        $enrollment->progress_percentage = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100, 1) 
            : 0;

        $enrollment->progress_details = [
            'total_modules' => $totalModules,
            'completed_modules' => $completedModules,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'total_assessments' => $totalAssessments,
            'completed_assessments' => $completedAssessments,
            'average_score' => round($averageScore, 1)
        ];

        return response()->json(['data' => $enrollment]);
    }

    /**
     * Update enrollment status
     * 
     * Update the status of an enrollment (e.g., drop from course).
     * 
     * @authenticated
     * @urlParam enrollment_id string required The enrollment ID. Example: "uuid-string"
     * @bodyParam status string required New status (dropped). Example: "dropped"
     * 
     * @response 200 {
     *   "data": {
     *     "enrollment_id": "uuid-string",
     *     "status": "dropped",
     *     "updated_at": "2024-01-20T10:30:00Z"
     *   },
     *   "message": "Enrollment status updated successfully"
     * }
     */
    public function update(Request $request, string $enrollment_id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:dropped'
        ]);

        $enrollment = auth()->user()->enrollments()
            ->where('enrollment_id', $enrollment_id)
            ->firstOrFail();

        // Only allow dropping active enrollments
        if ($enrollment->status !== 'active') {
            return response()->json([
                'message' => 'Can only drop active enrollments'
            ], 400);
        }

        $enrollment->update([
            'status' => $request->status,
            'completion_date' => now()
        ]);

        return response()->json([
            'data' => $enrollment->only(['enrollment_id', 'status', 'updated_at']),
            'message' => 'Enrollment status updated successfully'
        ]);
    }
}
