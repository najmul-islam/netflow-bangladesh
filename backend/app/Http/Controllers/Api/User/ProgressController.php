<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchLessonProgress;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="User Learning Progress",
 *     description="Endpoints for tracking learning progress (requires authentication)"
 * )
 */
class ProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/user/progress/course/{enrollment_id}",
     *     tags={"Learning Progress"},
     *     summary="Get course progress",
     *     description="Get detailed learning progress for a specific enrollment",
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
     *         description="Course progress retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="enrollment_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="course_title", type="string", example="Web Development Fundamentals"),
     *                 @OA\Property(property="overall_progress", type="number", format="float", example=65.5),
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="module_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="title", type="string", example="HTML Basics"),
     *                         @OA\Property(property="order_index", type="integer", example=1),
     *                         @OA\Property(property="progress_percentage", type="number", format="float", example=100),
     *                         @OA\Property(property="completed_lessons", type="integer", example=5),
     *                         @OA\Property(property="total_lessons", type="integer", example=5),
     *                         @OA\Property(
     *                             property="lessons",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="lesson_id", type="string", example="uuid-string"),
     *                                 @OA\Property(property="title", type="string", example="Introduction to HTML"),
     *                                 @OA\Property(property="order_index", type="integer", example=1),
     *                                 @OA\Property(property="duration_minutes", type="integer", example=30),
     *                                 @OA\Property(property="lesson_type", type="string", example="video"),
     *                                 @OA\Property(
     *                                     property="progress",
     *                                     type="object",
     *                                     @OA\Property(property="status", type="string", example="completed"),
     *                                     @OA\Property(property="completion_percentage", type="number", format="float", example=100),
     *                                     @OA\Property(property="time_spent_minutes", type="integer", example=35),
     *                                     @OA\Property(property="last_accessed_at", type="string", format="date-time", example="2024-01-20T14:30:00Z"),
     *                                     @OA\Property(property="completed_at", type="string", format="date-time", example="2024-01-18T16:45:00Z")
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="total_time_spent_hours", type="number", format="float", example=25.5),
     *                     @OA\Property(property="lessons_completed", type="integer", example=16),
     *                     @OA\Property(property="lessons_total", type="integer", example=24),
     *                     @OA\Property(property="modules_completed", type="integer", example=4),
     *                     @OA\Property(property="modules_total", type="integer", example=6),
     *                     @OA\Property(property="completion_rate", type="number", format="float", example=66.7)
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
    public function courseProgress(string $enrollment_id): JsonResponse
    {
        $enrollment = auth()->user()->enrollments()
            ->with([
                'batch.course:course_id,title',
                'batch.course.modules' => function ($query) {
                    $query->orderBy('order_index');
                },
                'batch.course.modules.lessons' => function ($query) {
                    $query->orderBy('order_index');
                }
            ])
            ->where('enrollment_id', $enrollment_id)
            ->firstOrFail();

        $course = $enrollment->batch->course;
        $userId = auth()->user()->user_id;

        // Get all progress records for this user and course
        $progressRecords = BatchLessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('lesson_id'))
            ->get()
            ->keyBy('lesson_id');

        $totalLessons = 0;
        $completedLessons = 0;
        $totalTimeSpent = 0;
        $completedModules = 0;

        // Process modules and lessons
        $modules = $course->modules->map(function ($module) use ($progressRecords, &$totalLessons, &$completedLessons, &$totalTimeSpent, &$completedModules) {
            $moduleLessons = $module->lessons;
            $moduleCompletedLessons = 0;

            $lessons = $moduleLessons->map(function ($lesson) use ($progressRecords, &$totalTimeSpent, &$moduleCompletedLessons) {
                $progress = $progressRecords->get($lesson->lesson_id);
                
                if ($progress) {
                    $totalTimeSpent += $progress->time_spent_minutes ?? 0;
                    if ($progress->status === 'completed') {
                        $moduleCompletedLessons++;
                    }
                }

                $lesson->progress = $progress ? [
                    'status' => $progress->status,
                    'completion_percentage' => $progress->completion_percentage ?? 0,
                    'time_spent_minutes' => $progress->time_spent_minutes ?? 0,
                    'last_accessed_at' => $progress->last_accessed_at,
                    'completed_at' => $progress->completed_at
                ] : [
                    'status' => 'not_started',
                    'completion_percentage' => 0,
                    'time_spent_minutes' => 0,
                    'last_accessed_at' => null,
                    'completed_at' => null
                ];

                return $lesson;
            });

            $totalLessons += $moduleLessons->count();
            $completedLessons += $moduleCompletedLessons;

            $moduleProgress = $moduleLessons->count() > 0 
                ? round(($moduleCompletedLessons / $moduleLessons->count()) * 100, 1)
                : 0;

            if ($moduleProgress === 100.0) {
                $completedModules++;
            }

            return [
                'module_id' => $module->module_id,
                'title' => $module->title,
                'order_index' => $module->order_index,
                'progress_percentage' => $moduleProgress,
                'completed_lessons' => $moduleCompletedLessons,
                'total_lessons' => $moduleLessons->count(),
                'lessons' => $lessons
            ];
        });

        $overallProgress = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100, 1)
            : 0;

        return response()->json([
            'data' => [
                'enrollment_id' => $enrollment_id,
                'course_title' => $course->title,
                'overall_progress' => $overallProgress,
                'modules' => $modules,
                'statistics' => [
                    'total_time_spent_hours' => round($totalTimeSpent / 60, 1),
                    'lessons_completed' => $completedLessons,
                    'lessons_total' => $totalLessons,
                    'modules_completed' => $completedModules,
                    'modules_total' => $course->modules->count(),
                    'completion_rate' => $overallProgress
                ]
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/progress/lesson/{lesson_id}",
     *     tags={"Learning Progress"},
     *     summary="Update lesson progress",
     *     description="Update or create progress for a specific lesson",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="lesson_id",
     *         in="path",
     *         required=true,
     *         description="The lesson ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status", "completion_percentage"},
     *             @OA\Property(property="status", type="string", enum={"in_progress", "completed"}, description="Progress status"),
     *             @OA\Property(property="completion_percentage", type="integer", minimum=0, maximum=100, description="Progress percentage (0-100)"),
     *             @OA\Property(property="time_spent_minutes", type="integer", minimum=0, description="Time spent on lesson in minutes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Progress updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="progress_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="lesson_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="completion_percentage", type="integer", example=100),
     *                 @OA\Property(property="time_spent_minutes", type="integer", example=35),
     *                 @OA\Property(property="completed_at", type="string", format="date-time", example="2024-01-20T16:45:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T16:45:00Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Progress updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Not enrolled in this lesson's course",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not enrolled in this lesson's course")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found")
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
    public function updateLessonProgress(Request $request, string $lesson_id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:in_progress,completed',
            'completion_percentage' => 'required|integer|min:0|max:100',
            'time_spent_minutes' => 'nullable|integer|min:0'
        ]);

        // Verify user is enrolled in a batch that contains this lesson
        $lesson = Lesson::with('module.course.batches.enrollments')
            ->where('lesson_id', $lesson_id)
            ->firstOrFail();

        $userEnrollment = $lesson->module->course->batches
            ->flatMap->enrollments
            ->where('user_id', auth()->user()->user_id)
            ->where('status', 'active')
            ->first();

        if (!$userEnrollment) {
            return response()->json([
                'message' => 'Not enrolled in this lesson\'s course'
            ], 403);
        }

        // Update or create progress
        $progress = BatchLessonProgress::updateOrCreate(
            [
                'user_id' => auth()->user()->user_id,
                'batch_id' => $userEnrollment->batch_id,
                'lesson_id' => $lesson_id
            ],
            [
                'status' => $request->status,
                'completion_percentage' => $request->completion_percentage,
                'time_spent_minutes' => $request->time_spent_minutes ?? 0,
                'last_accessed_at' => now(),
                'completed_at' => $request->status === 'completed' ? now() : null
            ]
        );

        return response()->json([
            'data' => $progress->only([
                'progress_id', 'lesson_id', 'status', 'completion_percentage',
                'time_spent_minutes', 'completed_at', 'updated_at'
            ]),
            'message' => 'Progress updated successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/progress/lesson/{lesson_id}/content",
     *     tags={"Learning Progress"},
     *     summary="Get lesson content",
     *     description="Get lesson content and resources for enrolled users",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="lesson_id",
     *         in="path",
     *         required=true,
     *         description="The lesson ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson content retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="lesson_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="title", type="string", example="Introduction to HTML"),
     *                 @OA\Property(property="description", type="string", example="Learn the basics of HTML structure"),
     *                 @OA\Property(property="content", type="string", example="HTML lesson content here..."),
     *                 @OA\Property(property="lesson_type", type="string", example="video"),
     *                 @OA\Property(property="duration_minutes", type="integer", example=30),
     *                 @OA\Property(property="order_index", type="integer", example=1),
     *                 @OA\Property(property="video_url", type="string", example="https://example.com/video.mp4"),
     *                 @OA\Property(property="is_preview", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="module",
     *                     type="object",
     *                     @OA\Property(property="module_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="title", type="string", example="HTML Basics"),
     *                     @OA\Property(property="order_index", type="integer", example=1)
     *                 ),
     *                 @OA\Property(
     *                     property="resources",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="resource_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="title", type="string", example="HTML Cheat Sheet"),
     *                         @OA\Property(property="description", type="string", example="Quick reference for HTML tags"),
     *                         @OA\Property(property="resource_type", type="string", example="document"),
     *                         @OA\Property(property="file_url", type="string", example="https://example.com/cheatsheet.pdf"),
     *                         @OA\Property(property="file_size_mb", type="number", format="float", example=2.5)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="progress",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="status", type="string", example="in_progress"),
     *                     @OA\Property(property="completion_percentage", type="integer", example=75),
     *                     @OA\Property(property="time_spent_minutes", type="integer", example=25)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Access denied. You must be enrolled to view this lesson.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lesson not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lesson not found")
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
    public function lessonContent(string $lesson_id): JsonResponse
    {
        // Verify access and get lesson
        $lesson = Lesson::with([
            'module:module_id,title,order_index',
            'resources',
            'module.course.batches.enrollments'
        ])
        ->where('lesson_id', $lesson_id)
        ->firstOrFail();

        $userEnrollment = $lesson->module->course->batches
            ->flatMap->enrollments
            ->where('user_id', auth()->user()->user_id)
            ->where('status', 'active')
            ->first();

        if (!$userEnrollment && !$lesson->is_preview) {
            return response()->json([
                'message' => 'Access denied. You must be enrolled to view this lesson.'
            ], 403);
        }

        // Get user's progress for this lesson
        $progress = null;
        if ($userEnrollment) {
            $progress = BatchLessonProgress::where('user_id', auth()->user()->user_id)
                ->where('lesson_id', $lesson_id)
                ->first();
        }

        $lesson->progress = $progress ? [
            'status' => $progress->status,
            'completion_percentage' => $progress->completion_percentage ?? 0,
            'time_spent_minutes' => $progress->time_spent_minutes ?? 0
        ] : null;

        return response()->json(['data' => $lesson]);
    }
}
