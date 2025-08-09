<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="NetFlow Bangladesh LMS API",
 *     version="1.0.0",
 *     description="Complete Learning Management System API with comprehensive functionality for course management, student enrollment, progress tracking, assessments, certificates, messaging, and more.",
 *     termsOfService="http://netflow-bd.com/terms",
 *     @OA\Contact(
 *         email="support@netflow-bd.com",
 *         name="NetFlow Bangladesh Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="NetFlow Bangladesh LMS API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer your-token-here"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and token management"
 * )
 * 
 * @OA\Tag(
 *     name="Public - Courses",
 *     description="Public course browsing and information"
 * )
 * 
 * @OA\Tag(
 *     name="Public - Categories", 
 *     description="Public category browsing"
 * )
 * 
 * @OA\Tag(
 *     name="Public - Batches",
 *     description="Public batch information"
 * )
 * 
 * @OA\Tag(
 *     name="User - Profile",
 *     description="User profile management"
 * )
 * 
 * @OA\Tag(
 *     name="User - Enrollments",
 *     description="Course enrollment management"
 * )
 * 
 * @OA\Tag(
 *     name="User - Progress",
 *     description="Learning progress tracking"
 * )
 * 
 * @OA\Tag(
 *     name="User - Assessments",
 *     description="Quizzes and assessment management"
 * )
 * 
 * @OA\Tag(
 *     name="User - Messages",
 *     description="Internal messaging system"
 * )
 * 
 * @OA\Tag(
 *     name="User - Notifications",
 *     description="System notifications management"
 * )
 * 
 * @OA\Tag(
 *     name="User - Payments",
 *     description="Payment processing and history"
 * )
 * 
 * @OA\Tag(
 *     name="User - Reviews",
 *     description="Course reviews and ratings"
 * )
 * 
 * @OA\Tag(
 *     name="User - Forums",
 *     description="Discussion forums and topics"
 * )
 * 
 * @OA\Tag(
 *     name="User - Schedule",
 *     description="Class scheduling and attendance"
 * )
 * 
 * @OA\Tag(
 *     name="User - Certificates",
 *     description="Certificate management and downloads"
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
