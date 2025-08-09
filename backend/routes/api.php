<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Public\CourseController as PublicCourseController;
use App\Http\Controllers\Api\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\Public\BatchController as PublicBatchController;
use App\Http\Controllers\Api\User\EnrollmentController;
use App\Http\Controllers\Api\User\ProgressController;
use App\Http\Controllers\Api\User\AssessmentController;
use App\Http\Controllers\Api\User\MessageController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\ForumController;
use App\Http\Controllers\Api\User\ScheduleController;
use App\Http\Controllers\Api\User\CertificateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API Routes (No Authentication Required)
Route::prefix('public')->group(function () {
    // Course Management
    Route::get('courses', [PublicCourseController::class, 'index']);
    Route::get('courses/{course_id}', [PublicCourseController::class, 'show']);
    Route::get('courses/{course_id}/curriculum', [PublicCourseController::class, 'getCurriculum']);
    Route::get('courses/{course_id}/instructors', [PublicCourseController::class, 'getInstructors']);
    Route::get('courses/{course_id}/reviews', [PublicCourseController::class, 'getReviews']);
    
    // Category Management
    Route::get('categories', [PublicCategoryController::class, 'index']);
    Route::get('categories/{category_id}', [PublicCategoryController::class, 'show']);
    Route::get('categories/{category_id}/courses', [PublicCategoryController::class, 'getCourses']);
    
    // Batch Information
    Route::get('batches', [PublicBatchController::class, 'index']);
    Route::get('batches/{batch_id}', [PublicBatchController::class, 'show']);
    Route::get('courses/{course_id}/batches', [PublicBatchController::class, 'getCourseBatches']);
});

// User API Routes (Authentication Required)
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    
    // User Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::post('/avatar', [ProfileController::class, 'uploadProfilePicture']);
        Route::patch('/password', [ProfileController::class, 'changePassword']);
        Route::get('/addresses', [ProfileController::class, 'getAddresses']);
        Route::post('/addresses', [ProfileController::class, 'addAddress']);
        Route::patch('/addresses/{address_id}', [ProfileController::class, 'updateAddress']);
        Route::delete('/addresses/{address_id}', [ProfileController::class, 'deleteAddress']);
    });
    
    // Enrollment Management
    Route::prefix('enrollments')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index']);
        Route::post('/', [EnrollmentController::class, 'store']);
        Route::get('/{enrollment_id}', [EnrollmentController::class, 'show']);
        Route::patch('/{enrollment_id}/status', [EnrollmentController::class, 'updateStatus']);
        Route::delete('/{enrollment_id}', [EnrollmentController::class, 'destroy']);
        Route::get('/{enrollment_id}/schedule', [EnrollmentController::class, 'getSchedule']);
        Route::post('/{enrollment_id}/attendance', [EnrollmentController::class, 'markAttendance']);
    });
    
    // Learning Progress
    Route::prefix('progress')->group(function () {
        Route::get('/', [ProgressController::class, 'index']);
        Route::get('/batch/{batch_id}', [ProgressController::class, 'getBatchProgress']);
        Route::post('/lessons/{lesson_id}/complete', [ProgressController::class, 'completeLesson']);
        Route::get('/lessons/{lesson_id}/content', [ProgressController::class, 'getLessonContent']);
        Route::post('/lessons/{lesson_id}/bookmark', [ProgressController::class, 'toggleBookmark']);
        Route::get('/bookmarks', [ProgressController::class, 'getBookmarks']);
        Route::post('/lessons/{lesson_id}/time', [ProgressController::class, 'updateTimeSpent']);
    });
    
    // Assessment Management
    Route::prefix('assessments')->group(function () {
        Route::get('/', [AssessmentController::class, 'index']);
        Route::post('/{assessment_id}/start', [AssessmentController::class, 'startAttempt']);
        Route::post('/attempts/{attempt_id}/submit', [AssessmentController::class, 'submitAnswers']);
        Route::get('/attempts/{attempt_id}/results', [AssessmentController::class, 'getResults']);
    });
    
    // Messaging System
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::patch('/{message_id}/read', [MessageController::class, 'markAsRead']);
    });
    
    // Forum System
    Route::prefix('forum')->group(function () {
        Route::get('/', [ForumController::class, 'getForums']);
        Route::get('/{forum_id}/topics', [ForumController::class, 'getForumTopics']);
        Route::post('/{forum_id}/topics', [ForumController::class, 'createTopic']);
        Route::get('/topics/{topic_id}', [ForumController::class, 'getTopic']);
        Route::patch('/topics/{topic_id}', [ForumController::class, 'updateTopic']);
        Route::delete('/topics/{topic_id}', [ForumController::class, 'deleteTopic']);
        Route::post('/topics/{topic_id}/replies', [ForumController::class, 'replyToTopic']);
        Route::patch('/replies/{reply_id}', [ForumController::class, 'updateReply']);
        Route::delete('/replies/{reply_id}', [ForumController::class, 'deleteReply']);
    });

    // Schedule & Attendance Management
    Route::prefix('schedule')->group(function () {
        Route::get('/', [ScheduleController::class, 'getSchedule']);
        Route::get('/upcoming', [ScheduleController::class, 'getUpcomingClasses']);
        Route::get('/{schedule_id}', [ScheduleController::class, 'getClassDetails']);
        Route::get('/{schedule_id}/resources', [ScheduleController::class, 'getClassResources']);
        Route::post('/{schedule_id}/attendance', [ScheduleController::class, 'markAttendance']);
        Route::get('/attendance/summary', [ScheduleController::class, 'getAttendanceSummary']);
    });
    
    // Certificate Management
    Route::prefix('certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'getCertificates']);
        Route::get('/stats', [CertificateController::class, 'getCertificateStats']);
        Route::get('/eligible-batches', [CertificateController::class, 'getEligibleBatches']);
        Route::post('/request', [CertificateController::class, 'requestCertificate']);
        Route::get('/{certificate_id}', [CertificateController::class, 'getCertificateDetails']);
        Route::get('/{certificate_id}/download', [CertificateController::class, 'downloadCertificate']);
    });
    
    // Notification Management
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{notification_id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        Route::patch('/preferences', [NotificationController::class, 'updatePreferences']);
    });
    
    // Payment Management
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/course', [PaymentController::class, 'initiateCoursePayment']);
        Route::post('/callback', [PaymentController::class, 'handleCallback']);
        Route::get('/{payment_id}', [PaymentController::class, 'show']);
    });
    
    // Review Management
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('/reviewable-batches', [ReviewController::class, 'getReviewableBatches']);
        Route::get('/{review_id}', [ReviewController::class, 'show']);
        Route::patch('/{review_id}', [ReviewController::class, 'update']);
        Route::delete('/{review_id}', [ReviewController::class, 'destroy']);
    });
});

// Certificate Verification (Public)
Route::post('certificates/verify', [CertificateController::class, 'verifyCertificate']);

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
