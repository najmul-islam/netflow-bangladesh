<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchReview;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group User API - Reviews
 * 
 * Endpoints for managing course reviews (requires authentication)
 */
class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user reviews
     * 
     * Get all reviews submitted by the authenticated user.
     * 
     * @authenticated
     * @queryParam per_page integer Number of reviews per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "review_id": "uuid-string",
     *       "rating": 5,
     *       "review_text": "Excellent course! Learned a lot about web development.",
     *       "is_public": true,
     *       "batch": {
     *         "batch_id": "uuid-string",
     *         "batch_name": "Web Dev Batch 2024-A",
     *         "course": {
     *           "course_id": "uuid-string",
     *           "title": "Web Development Fundamentals",
     *           "course_code": "WDF-2024"
     *         }
     *       },
     *       "created_at": "2024-01-20T15:30:00Z",
     *       "updated_at": "2024-01-20T15:30:00Z"
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
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);

        $reviews = BatchReview::with([
            'batch:batch_id,batch_name,course_id',
            'batch.course:course_id,title,course_code'
        ])
        ->where('user_id', auth()->user()->user_id)
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        return response()->json([
            'data' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage()
            ]
        ]);
    }

    /**
     * Submit course review
     * 
     * Submit a review for a completed course batch.
     * 
     * @authenticated
     * @bodyParam batch_id string required Batch ID to review.
     * @bodyParam rating integer required Rating from 1-5.
     * @bodyParam review_text string required Review content.
     * @bodyParam is_public boolean Make review public (default: true).
     * 
     * @response 201 {
     *   "data": {
     *     "review_id": "uuid-string",
     *     "batch_id": "uuid-string",
     *     "rating": 5,
     *     "review_text": "Amazing course! The instructor was very knowledgeable.",
     *     "is_public": true,
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "course": {
     *         "course_id": "uuid-string",
     *         "title": "Web Development Fundamentals"
     *       }
     *     },
     *     "created_at": "2024-01-20T16:00:00Z"
     *   },
     *   "message": "Review submitted successfully"
     * }
     * 
     * @response 400 {
     *   "message": "Cannot submit review",
     *   "errors": ["Not enrolled in this batch", "Batch not completed", "Review already exists"]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:course_batches,batch_id',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|min:10|max:1000',
            'is_public' => 'nullable|boolean'
        ]);

        // Check if user is enrolled in the batch
        $enrollment = auth()->user()->enrollments()
            ->where('batch_id', $request->batch_id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'Cannot submit review',
                'errors' => ['Not enrolled in this batch']
            ], 400);
        }

        // Check if batch is completed
        $batch = CourseBatch::where('batch_id', $request->batch_id)->first();
        if ($batch->status !== 'completed' && $enrollment->status !== 'completed') {
            return response()->json([
                'message' => 'Cannot submit review',
                'errors' => ['Batch not completed yet']
            ], 400);
        }

        // Check if review already exists
        $existingReview = BatchReview::where('user_id', auth()->user()->user_id)
            ->where('batch_id', $request->batch_id)
            ->exists();

        if ($existingReview) {
            return response()->json([
                'message' => 'Cannot submit review',
                'errors' => ['Review already exists for this batch']
            ], 400);
        }

        $review = BatchReview::create([
            'user_id' => auth()->user()->user_id,
            'batch_id' => $request->batch_id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'is_public' => $request->is_public ?? true
        ]);

        $review->load([
            'batch:batch_id,batch_name,course_id',
            'batch.course:course_id,title'
        ]);

        return response()->json([
            'data' => $review,
            'message' => 'Review submitted successfully'
        ], 201);
    }

    /**
     * Update review
     * 
     * Update an existing review.
     * 
     * @authenticated
     * @urlParam review_id string required The review ID. Example: "uuid-string"
     * @bodyParam rating integer Rating from 1-5.
     * @bodyParam review_text string Review content.
     * @bodyParam is_public boolean Make review public.
     * 
     * @response 200 {
     *   "data": {
     *     "review_id": "uuid-string",
     *     "rating": 4,
     *     "review_text": "Updated review text",
     *     "is_public": true,
     *     "updated_at": "2024-01-20T17:00:00Z"
     *   },
     *   "message": "Review updated successfully"
     * }
     */
    public function update(Request $request, string $review_id): JsonResponse
    {
        $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'review_text' => 'nullable|string|min:10|max:1000',
            'is_public' => 'nullable|boolean'
        ]);

        $review = BatchReview::where('review_id', $review_id)
            ->where('user_id', auth()->user()->user_id)
            ->firstOrFail();

        $updateData = array_filter([
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'is_public' => $request->is_public
        ], function ($value) {
            return $value !== null;
        });

        $review->update($updateData);

        return response()->json([
            'data' => $review->fresh(),
            'message' => 'Review updated successfully'
        ]);
    }

    /**
     * Delete review
     * 
     * Delete a review.
     * 
     * @authenticated
     * @urlParam review_id string required The review ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "message": "Review deleted successfully"
     * }
     */
    public function destroy(string $review_id): JsonResponse
    {
        $review = BatchReview::where('review_id', $review_id)
            ->where('user_id', auth()->user()->user_id)
            ->firstOrFail();

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Get review details
     * 
     * Get details of a specific review.
     * 
     * @authenticated
     * @urlParam review_id string required The review ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "review_id": "uuid-string",
     *     "rating": 5,
     *     "review_text": "Excellent course content and instruction quality.",
     *     "is_public": true,
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "start_date": "2024-01-01",
     *       "end_date": "2024-03-01",
     *       "course": {
     *         "course_id": "uuid-string",
     *         "title": "Web Development Fundamentals",
     *         "course_code": "WDF-2024",
     *         "description": "Learn the basics of web development"
     *       }
     *     },
     *     "created_at": "2024-01-20T15:30:00Z",
     *     "updated_at": "2024-01-20T15:30:00Z"
     *   }
     * }
     */
    public function show(string $review_id): JsonResponse
    {
        $review = BatchReview::with([
            'batch:batch_id,batch_name,start_date,end_date,course_id',
            'batch.course:course_id,title,course_code,description'
        ])
        ->where('review_id', $review_id)
        ->where('user_id', auth()->user()->user_id)
        ->firstOrFail();

        return response()->json(['data' => $review]);
    }

    /**
     * Get reviewable batches
     * 
     * Get list of completed batches that can be reviewed.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "course": {
     *         "course_id": "uuid-string",
     *         "title": "Web Development Fundamentals",
     *         "course_code": "WDF-2024"
     *       },
     *       "completion_date": "2024-03-01",
     *       "enrollment_status": "completed",
     *       "has_review": false
     *     }
     *   ]
     * }
     */
    public function getReviewableBatches(): JsonResponse
    {
        $user = auth()->user();

        // Get completed enrollments
        $completedEnrollments = $user->enrollments()
            ->with([
                'batch:batch_id,batch_name,course_id,end_date',
                'batch.course:course_id,title,course_code'
            ])
            ->where('status', 'completed')
            ->get();

        // Get existing reviews
        $existingReviews = BatchReview::where('user_id', $user->user_id)
            ->pluck('batch_id')
            ->toArray();

        $reviewableBatches = $completedEnrollments->map(function ($enrollment) use ($existingReviews) {
            return [
                'batch_id' => $enrollment->batch->batch_id,
                'batch_name' => $enrollment->batch->batch_name,
                'course' => [
                    'course_id' => $enrollment->batch->course->course_id,
                    'title' => $enrollment->batch->course->title,
                    'course_code' => $enrollment->batch->course->course_code
                ],
                'completion_date' => $enrollment->batch->end_date,
                'enrollment_status' => $enrollment->status,
                'has_review' => in_array($enrollment->batch->batch_id, $existingReviews)
            ];
        });

        return response()->json(['data' => $reviewableBatches]);
    }
}
