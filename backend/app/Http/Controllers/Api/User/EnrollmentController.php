<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchEnrollment;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="User Enrollments",
 *     description="Endpoints for managing course enrollments (requires authentication)"
 * )
 */
class EnrollmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/user/enrollments",
     *     tags={"User Enrollments"},
     *     summary="Get user enrollments",
     *     description="Get all enrollments for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by enrollment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending","active","completed","dropped","suspended"}, example="active")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (max 20)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User enrollments",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="enrollment_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="enrollment_date", type="string", example="2024-01-15"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="progress_percentage", type="number", example=65.5),
     *                     @OA\Property(property="completion_date", type="string", example=null)
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="last_page", type="integer", example=1)
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
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth()->user()->enrollments()
            ->with([
                'batch.course:course_id,title,thumbnail_url,difficulty_level',
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
     * @OA\Post(
     *     path="/api/user/enrollments",
     *     tags={"User Enrollments"},
     *     summary="Enroll in a batch",
     *     description="Enroll the authenticated user in a course batch",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"batch_id"},
     *             @OA\Property(property="batch_id", type="string", description="The batch ID to enroll in", example="uuid-string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successfully enrolled in the batch",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="enrollment_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="user_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="enrollment_date", type="string", example="2024-01-15"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(
     *                     property="batch",
     *                     type="object",
     *                     @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A"),
     *                     @OA\Property(property="price", type="number", format="float", example=99.99),
     *                     @OA\Property(property="currency", type="string", example="USD")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Successfully enrolled in the batch")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Enrollment not available",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment not available"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="array",
     *                 @OA\Items(type="string", example="Batch is full")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Already enrolled in this batch",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Already enrolled in this batch")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The batch id field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="batch_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The batch id field is required.")
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
            'batch:batch_id,batch_name,course_id',
            'batch.course:course_id,title,price,currency'
        ]);

        return response()->json([
            'data' => $enrollment,
            'message' => 'Successfully enrolled in the batch'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/user/enrollments/{enrollment_id}",
     *     tags={"User Enrollments"},
     *     summary="Get enrollment details",
     *     description="Get detailed information about a specific enrollment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="enrollment_id",
     *         in="path",
     *         required=true,
     *         description="The enrollment ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="enrollment_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="enrollment_date", type="string", example="2024-01-15"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="progress_percentage", type="number", format="float", example=65.5),
     *                 @OA\Property(property="completion_date", type="string", nullable=true, example=null),
     *                 @OA\Property(
     *                     property="batch",
     *                     type="object",
     *                     @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A"),
     *                     @OA\Property(property="start_date", type="string", example="2024-02-01"),
     *                     @OA\Property(property="end_date", type="string", example="2024-04-01"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(
     *                         property="course",
     *                         type="object",
     *                         @OA\Property(property="course_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="title", type="string", example="Web Development Fundamentals"),
     *                         @OA\Property(property="description", type="string", example="Learn web development from basics"),
     *                         @OA\Property(property="difficulty_level", type="string", example="beginner"),
     *                         @OA\Property(property="estimated_duration_hours", type="integer", example=40)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="progress_details",
     *                     type="object",
     *                     @OA\Property(property="total_modules", type="integer", example=6),
     *                     @OA\Property(property="completed_modules", type="integer", example=4),
     *                     @OA\Property(property="total_lessons", type="integer", example=24),
     *                     @OA\Property(property="completed_lessons", type="integer", example=16),
     *                     @OA\Property(property="total_assessments", type="integer", example=3),
     *                     @OA\Property(property="completed_assessments", type="integer", example=2),
     *                     @OA\Property(property="average_score", type="number", format="float", example=85.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Enrollment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment not found")
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
    public function show(string $enrollment_id): JsonResponse
    {
        $enrollment = auth()->user()->enrollments()
            ->with([
                'batch.course:course_id,title,description,difficulty_level,estimated_duration_hours',
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
     * @OA\Patch(
     *     path="/api/user/enrollments/{enrollment_id}",
     *     tags={"User Enrollments"},
     *     summary="Update enrollment status",
     *     description="Update the status of an enrollment (e.g., drop from course)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="enrollment_id",
     *         in="path",
     *         required=true,
     *         description="The enrollment ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"dropped"}, description="New status", example="dropped")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Enrollment status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="enrollment_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="status", type="string", example="dropped"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T10:30:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Enrollment status updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot drop enrollment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Can only drop active enrollments")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Enrollment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Enrollment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The status field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="status",
     *                     type="array",
     *                     @OA\Items(type="string", example="The status field is required.")
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
