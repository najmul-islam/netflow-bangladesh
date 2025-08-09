<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchLessonProgress;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group User API - Learning Progress
 * 
 * Endpoints for tracking learning progress (requires authentication)
 */
class ProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get course progress
     * 
     * Get detailed learning progress for a specific enrollment.
     * 
     * @authenticated
     * @urlParam enrollment_id string required The enrollment ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "enrollment_id": "uuid-string",
     *     "course_title": "Web Development Fundamentals",
     *     "overall_progress": 65.5,
     *     "modules": [
     *       {
     *         "module_id": "uuid-string",
     *         "title": "HTML Basics",
     *         "order_index": 1,
     *         "progress_percentage": 100,
     *         "completed_lessons": 5,
     *         "total_lessons": 5,
     *         "lessons": [
     *           {
     *             "lesson_id": "uuid-string",
     *             "title": "Introduction to HTML",
     *             "order_index": 1,
     *             "duration_minutes": 30,
     *             "lesson_type": "video",
     *             "progress": {
     *               "status": "completed",
     *               "completion_percentage": 100,
     *               "time_spent_minutes": 35,
     *               "last_accessed_at": "2024-01-20T14:30:00Z",
     *               "completed_at": "2024-01-18T16:45:00Z"
     *             }
     *           }
     *         ]
     *       }
     *     ],
     *     "statistics": {
     *       "total_time_spent_hours": 25.5,
     *       "lessons_completed": 16,
     *       "lessons_total": 24,
     *       "modules_completed": 4,
     *       "modules_total": 6,
     *       "completion_rate": 66.7
     *     }
     *   }
     * }
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
     * Update lesson progress
     * 
     * Update or create progress for a specific lesson.
     * 
     * @authenticated
     * @urlParam lesson_id string required The lesson ID. Example: "uuid-string"
     * @bodyParam status string required Progress status (in_progress, completed). Example: "completed"
     * @bodyParam completion_percentage integer Progress percentage (0-100). Example: 100
     * @bodyParam time_spent_minutes integer Time spent on lesson in minutes. Example: 35
     * 
     * @response 200 {
     *   "data": {
     *     "progress_id": "uuid-string",
     *     "lesson_id": "uuid-string",
     *     "status": "completed",
     *     "completion_percentage": 100,
     *     "time_spent_minutes": 35,
     *     "completed_at": "2024-01-20T16:45:00Z",
     *     "updated_at": "2024-01-20T16:45:00Z"
     *   },
     *   "message": "Progress updated successfully"
     * }
     * 
     * @response 403 {
     *   "message": "Not enrolled in this lesson's course"
     * }
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
     * Get lesson content
     * 
     * Get lesson content and resources for enrolled users.
     * 
     * @authenticated
     * @urlParam lesson_id string required The lesson ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "lesson_id": "uuid-string",
     *     "title": "Introduction to HTML",
     *     "description": "Learn the basics of HTML structure",
     *     "content": "HTML lesson content here...",
     *     "lesson_type": "video",
     *     "duration_minutes": 30,
     *     "order_index": 1,
     *     "video_url": "https://example.com/video.mp4",
     *     "is_preview": false,
     *     "module": {
     *       "module_id": "uuid-string",
     *       "title": "HTML Basics",
     *       "order_index": 1
     *     },
     *     "resources": [
     *       {
     *         "resource_id": "uuid-string",
     *         "title": "HTML Cheat Sheet",
     *         "description": "Quick reference for HTML tags",
     *         "resource_type": "document",
     *         "file_url": "https://example.com/cheatsheet.pdf",
     *         "file_size_mb": 2.5
     *       }
     *     ],
     *     "progress": {
     *       "status": "in_progress",
     *       "completion_percentage": 75,
     *       "time_spent_minutes": 25
     *     }
     *   }
     * }
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
