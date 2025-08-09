<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Public Courses",
 *     description="Public endpoints for browsing available courses"
 * )
 */
class CourseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/public/courses",
     *     tags={"Public Courses"},
     *     summary="List all published courses",
     *     description="Get a paginated list of all published courses available for enrollment",
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
     *         description="Number of items per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in course title and description",
     *         required=false,
     *         @OA\Schema(type="string", example="web development")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"title", "created_at", "price", "estimated_duration_hours"}, example="title")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="course_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="title", type="string", example="Web Development Fundamentals"),
     *                     @OA\Property(property="description", type="string", example="Learn the basics of web development"),
     *                     @OA\Property(property="short_description", type="string", example="Intro to web dev"),
     *                     @OA\Property(property="thumbnail_url", type="string", example="https://example.com/thumb.jpg"),
     *                     @OA\Property(property="price", type="number", example=99.99),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="estimated_duration_hours", type="integer", example=40),
     *                     @OA\Property(property="difficulty_level", type="string", example="beginner"),
     *                     @OA\Property(property="status", type="string", example="published"),
     *                     @OA\Property(property="instructor_count", type="integer", example=2),
     *                     @OA\Property(property="batch_count", type="integer", example=3),
     *                     @OA\Property(property="created_at", type="string", example="2024-01-01T00:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2024-01-01T00:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="last_page", type="integer", example=2)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::with(['category'])
            ->where('status', 'published')
            ->withCount(['instructors as instructor_count'])
            ->withCount(['batches as batch_count']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        $allowedSortFields = ['title', 'created_at', 'price', 'estimated_duration_hours'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 50);
        $courses = $query->paginate($perPage);

        return response()->json([
            'data' => $courses->items(),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'total' => $courses->total(),
                'per_page' => $courses->perPage(),
                'last_page' => $courses->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/courses/{course_id}",
     *     tags={"Public Courses"},
     *     summary="Get course details",
     *     description="Get detailed information about a specific published course",
     *     @OA\Parameter(
     *         name="course_id",
     *         in="path",
     *         description="The course ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="course_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="title", type="string", example="Web Development Fundamentals"),
     *                 @OA\Property(property="description", type="string", example="Complete description of the course..."),
     *                 @OA\Property(property="short_description", type="string", example="Intro to web dev"),
     *                 @OA\Property(property="thumbnail_url", type="string", example="https://example.com/thumb.jpg"),
     *                 @OA\Property(property="price", type="number", example=99.99),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="estimated_duration_hours", type="integer", example=40),
     *                 @OA\Property(property="difficulty_level", type="string", example="beginner"),
     *                 @OA\Property(property="prerequisites", type="string", example="Basic computer skills"),
     *                 @OA\Property(property="learning_outcomes", type="string", example="You will learn HTML, CSS, JavaScript"),
     *                 @OA\Property(property="status", type="string", example="published")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     )
     * )
     *         "batch_name": "Batch 2024-A",
     *         "start_date": "2024-02-01",
     *         "end_date": "2024-04-01",
     *         "enrollment_start": "2024-01-01",
     *         "enrollment_end": "2024-01-25",
     *         "max_students": 30,
     *         "enrolled_count": 15,
     *         "status": "open"
     *       }
     *     ],
     *     "instructors": [
     *       {
     *         "user_id": "uuid-string",
     *         "first_name": "John",
     *         "last_name": "Doe",
     *         "email": "john@example.com",
     *         "bio": "Experienced developer"
     *       }
     *     ],
     *     "reviews_summary": {
     *       "average_rating": 4.5,
     *       "total_reviews": 25,
     *       "rating_distribution": {
     *         "5": 15,
     *         "4": 8,
     *         "3": 2,
     *         "2": 0,
     *         "1": 0
     *       }
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Course not found"
     * }
     */
    public function show(string $course_id): JsonResponse
    {
        $course = Course::with([
            'category',
            'modules' => function ($query) {
                $query->orderBy('sort_order')->withCount('lessons');
            },
            'batches' => function ($query) {
                $query->where('status', 'open')
                      ->withCount('enrollments as enrolled_count')
                      ->orderBy('start_date');
            },
            'instructors' => function ($query) {
                $query->select('users.user_id', 'first_name', 'last_name', 'email', 'bio');
            }
        ])
        ->where('course_id', $course_id)
        ->where('status', 'published')
        ->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Get reviews summary
        $reviewsData = $course->batches()
            ->join('batch_reviews', 'course_batches.batch_id', '=', 'batch_reviews.batch_id')
            ->selectRaw('
                AVG(rating) as average_rating,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
            ')
            ->first();

        $course->reviews_summary = [
            'average_rating' => $reviewsData ? round($reviewsData->average_rating, 1) : 0,
            'total_reviews' => $reviewsData ? $reviewsData->total_reviews : 0,
            'rating_distribution' => [
                '5' => $reviewsData ? $reviewsData->rating_5 : 0,
                '4' => $reviewsData ? $reviewsData->rating_4 : 0,
                '3' => $reviewsData ? $reviewsData->rating_3 : 0,
                '2' => $reviewsData ? $reviewsData->rating_2 : 0,
                '1' => $reviewsData ? $reviewsData->rating_1 : 0,
            ]
        ];

        return response()->json(['data' => $course]);
    }

    /**
     * @OA\Get(
     *     path="/api/public/courses/{course_id}/curriculum",
     *     tags={"Public Courses"},
     *     summary="Get course curriculum",
     *     description="Get the detailed curriculum structure for a course including modules and lessons",
     *     @OA\Parameter(
     *         name="course_id",
     *         in="path",
     *         description="The course ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course curriculum",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="course_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="title", type="string", example="Web Development Fundamentals"),
     *                 @OA\Property(property="modules", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="module_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="title", type="string", example="Introduction to Web Development"),
     *                         @OA\Property(property="description", type="string", example="Basic concepts and setup"),
     *                         @OA\Property(property="sort_order", type="integer", example=1),
     *                         @OA\Property(property="duration_minutes", type="integer", example=120)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Course not found")
     *         )
     *     )
     * )
     *         "duration_minutes": 120,
     *         "lessons": [
     *           {
     *             "lesson_id": "uuid-string",
     *             "title": "What is Web Development?",
     *             "description": "Overview of web development",
     *             "sort_order": 1,
     *             "duration_minutes": 30,
     *             "is_preview": true,
     *             "lesson_type": "video",
     *             "resources_count": 3
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     */
    public function curriculum(string $course_id): JsonResponse
    {
        $course = Course::with([
            'modules' => function ($query) {
                $query->orderBy('sort_order');
            },
            'modules.lessons' => function ($query) {
                $query->orderBy('sort_order')
                      ->withCount('resources');
            }
        ])
        ->where('course_id', $course_id)
        ->where('status', 'published')
        ->select('course_id', 'title')
        ->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json(['data' => $course]);
    }
}
