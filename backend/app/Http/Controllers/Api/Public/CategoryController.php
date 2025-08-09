<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Public API - Categories
 * 
 * Public endpoints for browsing course categories
 */
class CategoryController extends Controller
{
    /**
     * List all categories
     * 
     * Get a list of all course categories with course counts.
     * 
     * @queryParam include_empty boolean Include categories with no courses. Example: false
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "category_id": "uuid-string",
     *       "name": "Programming",
     *       "description": "Learn programming languages and frameworks",
     *       "slug": "programming",
     *       "icon_url": "https://example.com/icon.png",
     *       "color": "#3B82F6",
     *       "is_active": true,
     *       "courses_count": 15,
     *       "created_at": "2024-01-01T00:00:00Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::withCount(['courses as courses_count' => function ($query) {
            $query->where('is_published', true);
        }])
        ->where('is_active', true)
        ->orderBy('name');

        if (!$request->boolean('include_empty', false)) {
            $query->having('courses_count', '>', 0);
        }

        $categories = $query->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Get category details
     * 
     * Get detailed information about a specific category including published courses.
     * 
     * @urlParam category_id string required The category ID. Example: "uuid-string"
     * @queryParam page integer Page number for courses pagination. Example: 1
     * @queryParam per_page integer Number of courses per page (max 20). Example: 10
     * 
     * @response 200 {
     *   "data": {
     *     "category_id": "uuid-string",
     *     "name": "Programming",
     *     "description": "Learn programming languages and frameworks",
     *     "slug": "programming",
     *     "icon_url": "https://example.com/icon.png",
     *     "color": "#3B82F6",
     *     "is_active": true,
     *     "courses": [
     *       {
     *         "course_id": "uuid-string",
     *         "title": "JavaScript Fundamentals",
     *         "short_description": "Learn JavaScript basics",
     *         "thumbnail_url": "https://example.com/thumb.jpg",
     *         "price": 89.99,
     *         "currency": "USD",
     *         "level": "beginner",
     *         "duration_hours": 25,
     *         "instructor_count": 1,
     *         "batch_count": 3
     *       }
     *     ],
     *     "courses_meta": {
     *       "current_page": 1,
     *       "total": 15,
     *       "per_page": 10,
     *       "last_page": 2
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Category not found"
     * }
     */
    public function show(Request $request, string $category_id): JsonResponse
    {
        $category = Category::where('category_id', $category_id)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Get paginated courses
        $perPage = min($request->get('per_page', 10), 20);
        $courses = $category->courses()
            ->where('is_published', true)
            ->withCount(['instructors as instructor_count', 'batches as batch_count'])
            ->select([
                'course_id', 'title', 'short_description', 'thumbnail_url', 
                'price', 'currency', 'level', 'duration_hours'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $category->courses = $courses->items();
        $category->courses_meta = [
            'current_page' => $courses->currentPage(),
            'total' => $courses->total(),
            'per_page' => $courses->perPage(),
            'last_page' => $courses->lastPage(),
        ];

        return response()->json(['data' => $category]);
    }
}
