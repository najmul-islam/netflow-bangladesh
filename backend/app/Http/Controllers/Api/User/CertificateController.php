<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchCertificate;
use App\Models\CertificateTemplate;
use App\Models\BatchEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="User Certificates",
 *     description="Endpoints for managing user certificates (requires authentication)"
 * )
 */
class CertificateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/certificates",
     *     tags={"User Certificates"},
     *     summary="Get user certificates",
     *     description="Get all certificates for the authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="batch_id",
     *         in="query",
     *         description="Filter by batch ID",
     *         required=false,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by certificate status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "issued", "revoked"}, example="issued")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User certificates retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="certificate_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="certificate_number", type="string", example="CERT-2024-001"),
     *                     @OA\Property(property="issued_date", type="string", example="2024-01-20"),
     *                     @OA\Property(property="status", type="string", example="issued"),
     *                     @OA\Property(property="course_title", type="string", example="Web Development Fundamentals"),
     *                     @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A"),
     *                     @OA\Property(property="completion_date", type="string", example="2024-01-18"),
     *                     @OA\Property(property="final_score", type="number", format="float", example=85.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
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
    public function getCertificates(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'batch_id' => 'nullable|exists:course_batches,batch_id',
                'status' => 'nullable|in:draft,issued,revoked',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $query = BatchCertificate::where('user_id', $user->user_id)
                ->with(['batch.course', 'template']);
            
            if ($request->batch_id) {
                $query->where('batch_id', $request->batch_id);
            }
            
            if ($request->status) {
                $query->where('status', $request->status);
            }
            
            $perPage = $request->per_page ?? 10;
            $certificates = $query->orderBy('issued_at', 'desc')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $certificates,
                'message' => 'Certificates retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificates',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user/certificates/{certificate_id}",
     *     tags={"User Certificates"},
     *     summary="Get certificate details",
     *     description="Get detailed information about a specific certificate",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="certificate_id",
     *         in="path",
     *         required=true,
     *         description="The certificate ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Certificate details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="certificate_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="certificate_number", type="string", example="CERT-2024-001"),
     *                 @OA\Property(property="issued_date", type="string", example="2024-01-20"),
     *                 @OA\Property(property="status", type="string", example="issued"),
     *                 @OA\Property(property="course_title", type="string", example="Web Development Fundamentals"),
     *                 @OA\Property(property="batch_name", type="string", example="Web Dev Batch 2024-A"),
     *                 @OA\Property(property="completion_date", type="string", example="2024-01-18"),
     *                 @OA\Property(property="final_score", type="number", format="float", example=85.5),
     *                 @OA\Property(property="certificate_url", type="string", example="https://example.com/certificates/cert-001.pdf"),
     *                 @OA\Property(
     *                     property="user_details",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Certificate details retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Certificate not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Certificate not found")
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
     *
     * @param string $certificateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCertificateDetails($certificateId)
    {
        try {
            $user = Auth::user();
            
            $certificate = BatchCertificate::where('user_id', $user->user_id)
                ->with(['batch.course', 'template', 'user'])
                ->findOrFail($certificateId);
            
            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate details retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificate details',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download certificate
     *
     * @param string $certificateId
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadCertificate($certificateId)
    {
        try {
            $user = Auth::user();
            
            $certificate = BatchCertificate::where('user_id', $user->user_id)
                ->where('status', 'issued')
                ->findOrFail($certificateId);
            
            if (!$certificate->certificate_file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate file not available'
                ], Response::HTTP_NOT_FOUND);
            }
            
            if (!Storage::exists($certificate->certificate_file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate file not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $fileName = 'certificate_' . $certificate->certificate_number . '.pdf';
            
            return Storage::download($certificate->certificate_file_path, $fileName);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download certificate',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify certificate
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyCertificate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'certificate_number' => 'required|string',
                'verification_code' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $certificate = BatchCertificate::where('certificate_number', $request->certificate_number)
                ->where('verification_code', $request->verification_code)
                ->where('status', 'issued')
                ->with(['user', 'batch.course'])
                ->first();
            
            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid certificate number or verification code'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Return only public information for verification
            $verificationData = [
                'certificate_number' => $certificate->certificate_number,
                'student_name' => $certificate->user->first_name . ' ' . $certificate->user->last_name,
                'course_name' => $certificate->batch->course->title,
                'batch_name' => $certificate->batch->batch_name,
                'issued_date' => $certificate->issued_at,
                'grade' => $certificate->grade,
                'status' => $certificate->status,
                'is_valid' => true
            ];
            
            return response()->json([
                'success' => true,
                'data' => $verificationData,
                'message' => 'Certificate verified successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify certificate',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get eligible batches for certificate generation
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEligibleBatches()
    {
        try {
            $user = Auth::user();
            
            // Get completed enrollments that don't have certificates yet
            $eligibleEnrollments = BatchEnrollment::where('user_id', $user->user_id)
                ->where('status', 'completed')
                ->where('final_exam_passed', true)
                ->where('certificate_issued', false)
                ->with(['batch.course'])
                ->whereDoesntHave('certificates')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $eligibleEnrollments,
                'message' => 'Eligible batches retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve eligible batches',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Request certificate generation
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestCertificate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'batch_id' => 'required|exists:course_batches,batch_id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = Auth::user();
            $batchId = $request->batch_id;
            
            // Verify user is eligible for certificate
            $enrollment = BatchEnrollment::where('user_id', $user->user_id)
                ->where('batch_id', $batchId)
                ->where('status', 'completed')
                ->where('final_exam_passed', true)
                ->where('certificate_issued', false)
                ->first();
            
            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not eligible for certificate. Requirements: completed course and passed final exam'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Check if certificate already exists
            $existingCertificate = BatchCertificate::where('user_id', $user->user_id)
                ->where('batch_id', $batchId)
                ->first();
            
            if ($existingCertificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate already exists for this batch'
                ], Response::HTTP_CONFLICT);
            }
            
            // Get default certificate template
            $template = CertificateTemplate::where('is_active', true)
                ->where('is_default', true)
                ->first();
            
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active certificate template found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            // Generate certificate number
            $certificateNumber = 'CERT-' . date('Y') . '-' . str_pad(BatchCertificate::count() + 1, 6, '0', STR_PAD_LEFT);
            
            // Generate verification code
            $verificationCode = strtoupper(substr(md5($user->user_id . $batchId . time()), 0, 8));
            
            // Create certificate record
            $certificate = BatchCertificate::create([
                'user_id' => $user->user_id,
                'batch_id' => $batchId,
                'template_id' => $template->template_id,
                'certificate_number' => $certificateNumber,
                'verification_code' => $verificationCode,
                'grade' => $enrollment->final_exam_score >= 90 ? 'A+' : 
                          ($enrollment->final_exam_score >= 80 ? 'A' : 
                          ($enrollment->final_exam_score >= 70 ? 'B+' : 
                          ($enrollment->final_exam_score >= 60 ? 'B' : 'C'))),
                'status' => 'draft',
                'requested_at' => now()
            ]);
            
            // Update enrollment
            $enrollment->update(['certificate_issued' => true]);
            
            $certificate->load(['batch.course', 'template']);
            
            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'Certificate request submitted successfully. It will be processed and issued shortly.'
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request certificate',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get certificate statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCertificateStats()
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total_certificates' => BatchCertificate::where('user_id', $user->user_id)->count(),
                'issued_certificates' => BatchCertificate::where('user_id', $user->user_id)
                    ->where('status', 'issued')->count(),
                'pending_certificates' => BatchCertificate::where('user_id', $user->user_id)
                    ->where('status', 'draft')->count(),
                'eligible_batches' => BatchEnrollment::where('user_id', $user->user_id)
                    ->where('status', 'completed')
                    ->where('final_exam_passed', true)
                    ->where('certificate_issued', false)
                    ->count(),
                'latest_certificate' => BatchCertificate::where('user_id', $user->user_id)
                    ->where('status', 'issued')
                    ->with(['batch.course'])
                    ->latest('issued_at')
                    ->first()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Certificate statistics retrieved successfully'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certificate statistics',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
