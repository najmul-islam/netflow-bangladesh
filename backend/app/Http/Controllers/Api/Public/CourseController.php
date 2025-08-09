<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Public API - Courses
 * 
 * Public endpoints for browsing available courses
 */
class CourseController extends Controller
{
    /**
     * List all published courses
     * 
     * Get a paginated list of all published courses available for enrollment.
     * 
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 50). Example: 15
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam search string Search in course title and description. Example: "web development"
     * @queryParam sort string Sort field (title, created_at, price). Example: title
     * @queryParam order string Sort order (asc, desc). Example: asc
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "course_id": "uuid-string",
     *       "title": "Web Development Fundamentals",
     *       "description": "Learn the basics of web development",
     *       "short_description": "Intro to web dev",
     *       "thumbnail_url": "https://example.com/thumb.jpg",
     *       "price": 99.99,
     *       "currency": "USD",
     *       "duration_hours": 40,
     *       "level": "beginner",
     *       "is_published": true,
     *       "category": {
     *         "category_id": "uuid-string",
     *         "name": "Programming",
     *         "description": "Programming courses"
     *       },
     *       "instructor_count": 2,
     *       "student_count": 150,
     *       "created_at": "2024-01-01T00:00:00Z",
     *       "updated_at": "2024-01-01T00:00:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 25,
     *     "per_page": 15,
     *     "last_page": 2
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::with(['category'])
            ->where('is_published', true)
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
        
        $allowedSortFields = ['title', 'created_at', 'price', 'duration_hours'];
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
     * Get course details
     * 
     * Get detailed information about a specific published course.
     * 
     * @urlParam course_id string required The course ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "course_id": "uuid-string",
     *     "title": "Web Development Fundamentals",
     *     "description": "Complete description of the course...",
     *     "short_description": "Intro to web dev",
     *     "thumbnail_url": "https://example.com/thumb.jpg",
     *     "price": 99.99,
     *     "currency": "USD",
     *     "duration_hours": 40,
     *     "level": "beginner",
     *     "prerequisites": "Basic computer skills",
     *     "learning_outcomes": "You will learn HTML, CSS, JavaScript",
     *     "is_published": true,
     *     "category": {
     *       "category_id": "uuid-string",
     *       "name": "Programming",
     *       "description": "Programming courses"
     *     },
     *     "modules": [
     *       {
     *         "module_id": "uuid-string",
     *         "title": "Introduction",
     *         "description": "Getting started",
     *         "order_index": 1,
     *         "lessons_count": 5
     *       }
     *     ],
     *     "batches": [
     *       {
     *         "batch_id": "uuid-string",
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
                $query->orderBy('order_index')->withCount('lessons');
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
        ->where('is_published', true)
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
     * Get course curriculum
     * 
     * Get the detailed curriculum structure for a course including modules and lessons.
     * 
     * @urlParam course_id string required The course ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "course_id": "uuid-string",
     *     "title": "Web Development Fundamentals",
     *     "modules": [
     *       {
     *         "module_id": "uuid-string",
     *         "title": "Introduction to Web Development",
     *         "description": "Basic concepts and setup",
     *         "order_index": 1,
     *         "duration_minutes": 120,
     *         "lessons": [
     *           {
     *             "lesson_id": "uuid-string",
     *             "title": "What is Web Development?",
     *             "description": "Overview of web development",
     *             "order_index": 1,
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
                $query->orderBy('order_index');
            },
            'modules.lessons' => function ($query) {
                $query->orderBy('order_index')
                      ->withCount('resources');
            }
        ])
        ->where('course_id', $course_id)
        ->where('is_published', true)
        ->select('course_id', 'title')
        ->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json(['data' => $course]);
    }
}
