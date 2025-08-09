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

class CertificateController extends Controller
{
    /**
     * Get user's certificates
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * Get specific certificate details
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
