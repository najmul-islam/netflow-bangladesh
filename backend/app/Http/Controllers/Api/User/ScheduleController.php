<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchSchedule;
use App\Models\ClassAttendance;
use App\Models\BatchEnrollment;
use App\Models\LessonResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Get user's class schedule
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSchedule(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'batch_id' => 'nullable|exists:course_batches,batch_id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'status' => 'nullable|in:scheduled,ongoing,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            // Get user's enrolled batches
            $enrolledBatchesQuery = BatchEnrollment::where('user_id', $user->user_id)
                ->where('status', 'active');
            
            if ($request->batch_id) {
                $enrolledBatchesQuery->where('batch_id', $request->batch_id);
            }
            
            $enrolledBatchIds = $enrolledBatchesQuery->pluck('batch_id');
            
            if ($enrolledBatchIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No enrolled batches found'
                ], Response::HTTP_OK);
            }
            
            $scheduleQuery = BatchSchedule::whereIn('batch_id', $enrolledBatchIds)
                ->with(['batch.course', 'lesson', 'resources', 'attendance' => function ($query) use ($user) {
                    $query->where('user_id', $user->user_id);
                }]);
            
            if ($request->date_from) {
                $scheduleQuery->where('scheduled_date', '>=', $request->date_from);
            }
            
            if ($request->date_to) {
                $scheduleQuery->where('scheduled_date', '<=', $request->date_to);
            }
            
            if ($request->status) {
                $scheduleQuery->where('status', $request->status);
            }
            
            $schedules = $scheduleQuery->orderBy('scheduled_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $schedules,
                'message' => 'Schedule retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedule',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get specific class details
     *
     * @param string $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassDetails($scheduleId)
    {
        try {
            $user = Auth::user();
            
            $schedule = BatchSchedule::with([
                'batch.course',
                'lesson.resources',
                'resources',
                'attendance' => function ($query) use ($user) {
                    $query->where('user_id', $user->user_id);
                }
            ])->findOrFail($scheduleId);
            
            // Verify user has access to this class
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $schedule->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], Response::HTTP_FORBIDDEN);
            }
            
            return response()->json([
                'success' => true,
                'data' => $schedule,
                'message' => 'Class details retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class details',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mark attendance for a class
     *
     * @param \Illuminate\Http\Request $request
     * @param string $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAttendance(Request $request, $scheduleId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:present,absent,late',
                'check_in_time' => 'nullable|date_format:H:i:s',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();
            $schedule = BatchSchedule::findOrFail($scheduleId);
            
            // Verify user has access to this class
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $schedule->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Check if class is ongoing or completed
            if (!in_array($schedule->status, ['ongoing', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance can only be marked for ongoing or completed classes'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $attendance = ClassAttendance::updateOrCreate(
                [
                    'schedule_id' => $scheduleId,
                    'user_id' => $user->user_id
                ],
                [
                    'batch_id' => $schedule->batch_id,
                    'status' => $request->status,
                    'check_in_time' => $request->check_in_time ?? now()->format('H:i:s'),
                    'notes' => $request->notes,
                    'marked_by' => $user->user_id,
                    'marked_at' => now()
                ]
            );
            
            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Attendance marked successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's attendance summary
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttendanceSummary(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'batch_id' => 'nullable|exists:course_batches,batch_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $enrolledBatchesQuery = BatchEnrollment::where('user_id', $user->user_id)
                ->where('status', 'active');
            
            if ($request->batch_id) {
                $enrolledBatchesQuery->where('batch_id', $request->batch_id);
            }
            
            $enrolledBatches = $enrolledBatchesQuery->with('batch.course')->get();
            
            $summary = [];
            
            foreach ($enrolledBatches as $enrollment) {
                $batchId = $enrollment->batch_id;
                
                // Total scheduled classes
                $totalClasses = BatchSchedule::where('batch_id', $batchId)
                    ->whereIn('status', ['completed', 'ongoing'])
                    ->count();
                
                // User's attendance
                $attendanceData = ClassAttendance::where('batch_id', $batchId)
                    ->where('user_id', $user->user_id)
                    ->selectRaw('
                        COUNT(*) as total_marked,
                        SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late
                    ')
                    ->first();
                
                $presentCount = $attendanceData->present ?? 0;
                $attendancePercentage = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 2) : 0;
                
                $summary[] = [
                    'batch' => $enrollment->batch,
                    'total_classes' => $totalClasses,
                    'present' => $presentCount,
                    'absent' => $attendanceData->absent ?? 0,
                    'late' => $attendanceData->late ?? 0,
                    'attendance_percentage' => $attendancePercentage,
                    'not_marked' => $totalClasses - ($attendanceData->total_marked ?? 0)
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Attendance summary retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance summary',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get upcoming classes
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpcomingClasses(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'days_ahead' => 'nullable|integer|min:1|max:30'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $limit = $request->limit ?? 10;
            $daysAhead = $request->days_ahead ?? 7;
            
            // Get user's enrolled batches
            $enrolledBatchIds = BatchEnrollment::where('user_id', $user->user_id)
                ->where('status', 'active')
                ->pluck('batch_id');
            
            if ($enrolledBatchIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No enrolled batches found'
                ], Response::HTTP_OK);
            }
            
            $upcomingClasses = BatchSchedule::whereIn('batch_id', $enrolledBatchIds)
                ->where('status', 'scheduled')
                ->where('scheduled_date', '>=', now()->toDateString())
                ->where('scheduled_date', '<=', now()->addDays($daysAhead)->toDateString())
                ->with(['batch.course', 'lesson'])
                ->orderBy('scheduled_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $upcomingClasses,
                'message' => 'Upcoming classes retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming classes',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get class resources
     *
     * @param string $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassResources($scheduleId)
    {
        try {
            $user = Auth::user();
            
            $schedule = BatchSchedule::with(['resources', 'lesson.resources'])
                ->findOrFail($scheduleId);
            
            // Verify user has access to this class
            $hasAccess = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $schedule->batch_id)
                ->where('status', 'active')
                ->exists();
            
            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this class'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Combine schedule-specific resources and lesson resources
            $allResources = collect();
            
            if ($schedule->resources) {
                $allResources = $allResources->merge($schedule->resources);
            }
            
            if ($schedule->lesson && $schedule->lesson->resources) {
                $allResources = $allResources->merge($schedule->lesson->resources);
            }
            
            // Remove duplicates based on resource type and file path
            $uniqueResources = $allResources->unique(function ($resource) {
                return $resource->resource_type . '|' . $resource->file_path;
            });
            
            return response()->json([
                'success' => true,
                'data' => $uniqueResources->values(),
                'message' => 'Class resources retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve class resources',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
