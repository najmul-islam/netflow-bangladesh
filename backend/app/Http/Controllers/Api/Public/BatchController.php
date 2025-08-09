<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Public Batches",
 *     description="Public endpoints for browsing available course batches"
 * )
 */
class BatchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/public/batches",
     *     tags={"Public Batches"},
     *     summary="List available batches",
     *     description="Get a list of batches that are open for enrollment",
     *     @OA\Parameter(
     *         name="course_id",
     *         in="query",
     *         description="Filter by course ID",
     *         required=false,
     *         @OA\Schema(type="string", example="uuid-string")
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
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="batch_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A"),
     *                     @OA\Property(property="start_date", type="string", example="2024-02-01"),
     *                     @OA\Property(property="end_date", type="string", example="2024-04-01"),
     *                     @OA\Property(property="enrollment_start", type="string", example="2024-01-01"),
     *                     @OA\Property(property="enrollment_end", type="string", example="2024-01-25"),
     *                     @OA\Property(property="max_students", type="integer", example=30),
     *                     @OA\Property(property="enrolled_count", type="integer", example=15),
     *                     @OA\Property(property="available_spots", type="integer", example=15),
     *                     @OA\Property(property="status", type="string", example="open"),
     *                     @OA\Property(property="price", type="number", example=99.99),
     *                     @OA\Property(property="currency", type="string", example="USD")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=8),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="last_page", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     *       "course": {
     *         "course_id": "uuid-string",
     *         "title": "Web Development Fundamentals",
     *         "difficulty_level": "beginner",
     *         "estimated_duration_hours": 40
     *       },
     *       "instructors": [
     *         {
     *           "user_id": "uuid-string",
     *           "first_name": "John",
     *           "last_name": "Doe",
     *           "bio": "Experienced web developer"
     *         }
     *       ]
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 8,
     *     "per_page": 10,
     *     "last_page": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = CourseBatch::with([
            'course:course_id,title,difficulty_level,estimated_duration_hours',
            'instructors:user_id,first_name,last_name,bio'
        ])
        ->withCount('enrollments as enrolled_count')
        ->where('status', 'open')
        ->where('enrollment_start', '<=', now())
        ->where('enrollment_end', '>=', now());

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $query->orderBy('start_date');

        $perPage = min($request->get('per_page', 10), 20);
        $batches = $query->paginate($perPage);

        // Add available spots calculation
        $batches->getCollection()->transform(function ($batch) {
            $batch->available_spots = $batch->max_students - $batch->enrolled_count;
            return $batch;
        });

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'last_page' => $batches->lastPage(),
            ]
        ]);
    }

    /**
     * Get batch details
     * 
     * Get detailed information about a specific batch.
     * 
     * @urlParam batch_id string required The batch ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "batch_id": "uuid-string",
     *     "batch_name": "Web Dev Batch 2024-A",
     *     "description": "Comprehensive web development course",
     *     "start_date": "2024-02-01",
     *     "end_date": "2024-04-01",
     *     "enrollment_start": "2024-01-01",
     *     "enrollment_end": "2024-01-25",
     *     "max_students": 30,
     *     "enrolled_count": 15,
     *     "available_spots": 15,
     *     "status": "open",
     *     "price": 99.99,
     *     "currency": "USD",
     *     "schedule": "Mon-Wed-Fri 7:00-9:00 PM",
     *     "location": "Online",
     *     "timezone": "UTC",
     *     "course": {
     *       "course_id": "uuid-string",
     *       "title": "Web Development Fundamentals",
     *       "description": "Learn web development from scratch",
     *       "difficulty_level": "beginner",
     *       "estimated_duration_hours": 40,
     *       "prerequisites": "Basic computer skills"
     *     },
     *     "instructors": [
     *       {
     *         "user_id": "uuid-string",
     *         "first_name": "John",
     *         "last_name": "Doe",
     *         "email": "john@example.com",
     *         "bio": "Experienced web developer with 5+ years",
     *         "expertise": ["JavaScript", "React", "Node.js"]
     *       }
     *     ],
     *     "schedule_overview": [
     *       {
     *         "week": 1,
     *         "topic": "HTML Basics",
     *         "description": "Introduction to HTML structure"
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Batch not found"
     * }
     */
    public function show(string $batch_id): JsonResponse
    {
        $batch = CourseBatch::with([
            'course:course_id,title,description,difficulty_level,estimated_duration_hours,prerequisites',
            'instructors:user_id,first_name,last_name,email,bio,expertise'
        ])
        ->withCount('enrollments as enrolled_count')
        ->where('batch_id', $batch_id)
        ->where('status', 'open')
        ->first();

        if (!$batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        $batch->available_spots = $batch->max_students - $batch->enrolled_count;

        // Get schedule overview (basic structure)
        $scheduleOverview = $batch->scheduleTopics()
            ->select('week_number as week', 'topic_title as topic', 'topic_description as description')
            ->orderBy('week_number')
            ->get();

        $batch->schedule_overview = $scheduleOverview;

        return response()->json(['data' => $batch]);
    }
}
