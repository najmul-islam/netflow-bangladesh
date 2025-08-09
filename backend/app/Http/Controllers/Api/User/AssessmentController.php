<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BatchAssessment;
use App\Models\BatchAssessmentAttempt;
use App\Models\BatchQuestion;
use App\Models\BatchQuestionResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="User Assessments",
 *     description="Endpoints for taking assessments and viewing results (requires authentication)"
 * )
 */
class AssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/user/assessments",
     *     tags={"User Assessments"},
     *     summary="Get available assessments",
     *     description="Get all assessments for user's enrolled batches",
     *     security={{"bearerAuth":{}}},
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
     *         description="Filter by assessment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"upcoming","active","completed","expired"}, example="active")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Available assessments",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="assessment_id", type="string", example="uuid-string"),
     *                     @OA\Property(property="title", type="string", example="HTML & CSS Quiz"),
     *                     @OA\Property(property="description", type="string", example="Test your knowledge of HTML and CSS"),
     *                     @OA\Property(property="assessment_type", type="string", example="quiz"),
     *                     @OA\Property(property="total_marks", type="integer", example=50),
     *                     @OA\Property(property="passing_marks", type="integer", example=35),
     *                     @OA\Property(property="duration_minutes", type="integer", example=60),
     *                     @OA\Property(property="start_date", type="string", example="2024-01-20T09:00:00Z"),
     *                     @OA\Property(property="end_date", type="string", example="2024-01-22T23:59:59Z"),
     *                     @OA\Property(property="max_attempts", type="integer", example=3),
     *                     @OA\Property(property="status", type="string", example="active")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     *         "batch_name": "Web Dev Batch 2024-A",
     *         "course_title": "Web Development Fundamentals"
     *       },
     *       "user_attempts": {
     *         "attempts_count": 1,
     *         "best_score": 42,
     *         "latest_attempt": {
     *           "attempt_id": "uuid-string",
     *           "score": 42,
     *           "status": "completed",
     *           "completed_at": "2024-01-20T10:30:00Z"
     *         }
     *       }
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $userBatchIds = auth()->user()->enrollments()
            ->where('status', 'active')
            ->pluck('batch_id');

        $query = BatchAssessment::with([
            'batch:batch_id,batch_name,course_id',
            'batch.course:course_id,title'
        ])
        ->whereIn('batch_id', $userBatchIds)
        ->where('is_published', true);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $now = now();

            switch ($status) {
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'active':
                    $query->where('start_date', '<=', $now)
                          ->where('end_date', '>=', $now);
                    break;
                case 'expired':
                    $query->where('end_date', '<', $now);
                    break;
            }
        }

        $assessments = $query->orderBy('start_date')->get();

        // Add user attempt data
        $assessments->each(function ($assessment) {
            $attempts = BatchAssessmentAttempt::where('assessment_id', $assessment->assessment_id)
                ->where('user_id', auth()->user()->user_id)
                ->orderBy('created_at', 'desc')
                ->get();

            $assessment->user_attempts = [
                'attempts_count' => $attempts->count(),
                'best_score' => $attempts->where('status', 'completed')->max('score') ?? 0,
                'latest_attempt' => $attempts->first() ? [
                    'attempt_id' => $attempts->first()->attempt_id,
                    'score' => $attempts->first()->score,
                    'status' => $attempts->first()->status,
                    'completed_at' => $attempts->first()->completed_at
                ] : null
            ];

            // Determine assessment status
            $now = now();
            if ($now < $assessment->start_date) {
                $assessment->status = 'upcoming';
            } elseif ($now <= $assessment->end_date) {
                $assessment->status = 'active';
            } else {
                $assessment->status = 'expired';
            }
        });

        return response()->json(['data' => $assessments]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/assessments/{assessment_id}/start",
     *     tags={"User Assessments"},
     *     summary="Start assessment attempt",
     *     description="Start a new attempt for an assessment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="assessment_id",
     *         in="path",
     *         description="The assessment ID",
     *         required=true,
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assessment attempt started",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="attempt_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="assessment_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="started_at", type="string", example="2024-01-20T14:30:00Z"),
     *                 @OA\Property(property="time_limit_minutes", type="integer", example=60),
     *                 @OA\Property(property="expires_at", type="string", example="2024-01-20T15:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot start assessment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot start assessment"),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     *         "question_text": "What does HTML stand for?",
     *         "question_type": "multiple_choice",
     *         "marks": 5,
     *         "sort_order": 1,
     *         "options": [
     *           {
     *             "option_id": "uuid-string",
     *             "option_text": "HyperText Markup Language",
     *             "sort_order": 1
     *           },
     *           {
     *             "option_id": "uuid-string",
     *             "option_text": "High Tech Modern Language",
     *             "sort_order": 2
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 400 {
     *   "message": "Cannot start assessment",
     *   "errors": ["Assessment not active", "Maximum attempts reached"]
     * }
     */
    public function startAttempt(string $assessment_id): JsonResponse
    {
        $assessment = BatchAssessment::with([
            'questions' => function ($query) {
                $query->orderBy('sort_order');
            },
            'questions.options' => function ($query) {
                $query->orderBy('sort_order');
            }
        ])
        ->where('assessment_id', $assessment_id)
        ->where('is_published', true)
        ->firstOrFail();

        // Verify user is enrolled in the batch
        $enrollment = auth()->user()->enrollments()
            ->where('batch_id', $assessment->batch_id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'message' => 'Not enrolled in this batch'
            ], 403);
        }

        // Validate assessment availability
        $errors = [];
        $now = now();

        if ($now < $assessment->start_date) {
            $errors[] = 'Assessment not started yet';
        }

        if ($now > $assessment->end_date) {
            $errors[] = 'Assessment has expired';
        }

        // Check attempt limits
        $attemptsCount = BatchAssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', auth()->user()->user_id)
            ->count();

        if ($assessment->max_attempts && $attemptsCount >= $assessment->max_attempts) {
            $errors[] = 'Maximum attempts reached';
        }

        // Check for ongoing attempt
        $ongoingAttempt = BatchAssessmentAttempt::where('assessment_id', $assessment_id)
            ->where('user_id', auth()->user()->user_id)
            ->where('status', 'in_progress')
            ->first();

        if ($ongoingAttempt) {
            $errors[] = 'You already have an ongoing attempt';
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Cannot start assessment',
                'errors' => $errors
            ], 400);
        }

        // Create new attempt
        $attempt = BatchAssessmentAttempt::create([
            'user_id' => auth()->user()->user_id,
            'assessment_id' => $assessment_id,
            'started_at' => now(),
            'time_limit_minutes' => $assessment->duration_minutes,
            'status' => 'in_progress'
        ]);

        $attempt->expires_at = $attempt->started_at->addMinutes($assessment->duration_minutes);

        // Remove correct answers from options for security
        $assessment->questions->each(function ($question) {
            $question->options->each(function ($option) {
                unset($option->is_correct);
            });
        });

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->attempt_id,
                'assessment_id' => $assessment_id,
                'started_at' => $attempt->started_at,
                'time_limit_minutes' => $attempt->time_limit_minutes,
                'expires_at' => $attempt->expires_at,
                'questions' => $assessment->questions
            ]
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/user/assessments/attempts/{attempt_id}/submit",
     *     tags={"User Assessments"},
     *     summary="Submit assessment answers",
     *     description="Submit answers for an assessment attempt",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="attempt_id",
     *         in="path",
     *         required=true,
     *         description="The attempt ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 description="Array of question answers",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"question_id"},
     *                     @OA\Property(property="question_id", type="string", description="Question ID"),
     *                     @OA\Property(
     *                         property="selected_options",
     *                         type="array",
     *                         description="Array of selected option IDs",
     *                         @OA\Items(type="string")
     *                     ),
     *                     @OA\Property(property="text_answer", type="string", description="Text answer for essay questions")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="attempt_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="score", type="integer", example=42),
     *                 @OA\Property(property="total_marks", type="integer", example=50),
     *                 @OA\Property(property="percentage", type="number", format="float", example=84),
     *                 @OA\Property(property="passed", type="boolean", example=true),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time", example="2024-01-20T15:25:00Z"),
     *                 @OA\Property(property="time_taken_minutes", type="integer", example=55),
     *                 @OA\Property(property="correct_answers", type="integer", example=8),
     *                 @OA\Property(property="total_questions", type="integer", example=10)
     *             ),
     *             @OA\Property(property="message", type="string", example="Assessment submitted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot submit assessment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot submit assessment"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="array",
     *                 @OA\Items(type="string", example="Time limit exceeded")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attempt not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Attempt not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The answers field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="answers",
     *                     type="array",
     *                     @OA\Items(type="string", example="The answers field is required.")
     *                 )
     *             )
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
    public function submitAnswers(Request $request, string $attempt_id): JsonResponse
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:batch_questions,question_id',
            'answers.*.selected_options' => 'nullable|array',
            'answers.*.selected_options.*' => 'exists:batch_question_options,option_id',
            'answers.*.text_answer' => 'nullable|string'
        ]);

        $attempt = BatchAssessmentAttempt::with('assessment')
            ->where('attempt_id', $attempt_id)
            ->where('user_id', auth()->user()->user_id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        // Check time limit
        $timeExpired = $attempt->started_at->addMinutes($attempt->time_limit_minutes);
        if (now() > $timeExpired) {
            return response()->json([
                'message' => 'Cannot submit assessment',
                'errors' => ['Time limit exceeded']
            ], 400);
        }

        DB::transaction(function () use ($request, $attempt) {
            $totalScore = 0;
            $correctAnswers = 0;
            $totalQuestions = 0;

            foreach ($request->answers as $answerData) {
                $question = BatchQuestion::with('options')
                    ->where('question_id', $answerData['question_id'])
                    ->where('assessment_id', $attempt->assessment_id)
                    ->first();

                if (!$question) continue;

                $totalQuestions++;

                // Create response record
                $response = BatchQuestionResponse::create([
                    'user_id' => auth()->user()->user_id,
                    'question_id' => $question->question_id,
                    'attempt_id' => $attempt->attempt_id,
                    'text_answer' => $answerData['text_answer'] ?? null,
                    'selected_options' => $answerData['selected_options'] ?? [],
                    'is_correct' => false,
                    'marks_awarded' => 0
                ]);

                // Calculate score for objective questions
                if (in_array($question->question_type, ['multiple_choice', 'single_choice', 'true_false'])) {
                    $correctOptions = $question->options->where('is_correct', true)->pluck('option_id')->toArray();
                    $selectedOptions = $answerData['selected_options'] ?? [];

                    $isCorrect = !array_diff($correctOptions, $selectedOptions) && 
                                !array_diff($selectedOptions, $correctOptions);

                    if ($isCorrect) {
                        $correctAnswers++;
                        $totalScore += $question->marks;
                        $response->update([
                            'is_correct' => true,
                            'marks_awarded' => $question->marks
                        ]);
                    }
                }
            }

            // Update attempt
            $attempt->update([
                'status' => 'completed',
                'completed_at' => now(),
                'score' => $totalScore,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'time_taken_minutes' => now()->diffInMinutes($attempt->started_at)
            ]);
        });

        $attempt->refresh();
        $assessment = $attempt->assessment;

        $percentage = $assessment->total_marks > 0 
            ? round(($attempt->score / $assessment->total_marks) * 100, 1)
            : 0;

        $passed = $assessment->passing_marks 
            ? $attempt->score >= $assessment->passing_marks
            : false;

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->attempt_id,
                'score' => $attempt->score,
                'total_marks' => $assessment->total_marks,
                'percentage' => $percentage,
                'passed' => $passed,
                'status' => $attempt->status,
                'completed_at' => $attempt->completed_at,
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions
            ],
            'message' => 'Assessment submitted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/assessments/attempts/{attempt_id}/results",
     *     tags={"User Assessments"},
     *     summary="Get attempt results",
     *     description="Get detailed results for a completed assessment attempt",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="attempt_id",
     *         in="path",
     *         required=true,
     *         description="The attempt ID",
     *         @OA\Schema(type="string", example="uuid-string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment results retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="attempt_id", type="string", example="uuid-string"),
     *                 @OA\Property(property="assessment_title", type="string", example="HTML & CSS Quiz"),
     *                 @OA\Property(property="score", type="integer", example=42),
     *                 @OA\Property(property="total_marks", type="integer", example=50),
     *                 @OA\Property(property="percentage", type="number", format="float", example=84),
     *                 @OA\Property(property="passed", type="boolean", example=true),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="started_at", type="string", format="date-time", example="2024-01-20T14:30:00Z"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time", example="2024-01-20T15:25:00Z"),
     *                 @OA\Property(property="time_taken_minutes", type="integer", example=55),
     *                 @OA\Property(
     *                     property="questions_summary",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=10),
     *                     @OA\Property(property="correct", type="integer", example=8),
     *                     @OA\Property(property="incorrect", type="integer", example=2),
     *                     @OA\Property(property="unanswered", type="integer", example=0)
     *                 ),
     *                 @OA\Property(
     *                     property="detailed_responses",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="question_id", type="string", example="uuid-string"),
     *                         @OA\Property(property="question_text", type="string", example="What does HTML stand for?"),
     *                         @OA\Property(
     *                             property="your_answer",
     *                             type="array",
     *                             @OA\Items(type="string", example="HyperText Markup Language")
     *                         ),
     *                         @OA\Property(
     *                             property="correct_answer",
     *                             type="array",
     *                             @OA\Items(type="string", example="HyperText Markup Language")
     *                         ),
     *                         @OA\Property(property="is_correct", type="boolean", example=true),
     *                         @OA\Property(property="marks_awarded", type="integer", example=5),
     *                         @OA\Property(property="marks_possible", type="integer", example=5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attempt not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Attempt not found")
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
    public function getResults(string $attempt_id): JsonResponse
    {
        $attempt = BatchAssessmentAttempt::with([
            'assessment:assessment_id,title,total_marks,passing_marks',
            'responses.question:question_id,question_text,marks',
            'responses.question.options:option_id,question_id,option_text,is_correct'
        ])
        ->where('attempt_id', $attempt_id)
        ->where('user_id', auth()->user()->user_id)
        ->where('status', 'completed')
        ->firstOrFail();

        $assessment = $attempt->assessment;
        $percentage = $assessment->total_marks > 0 
            ? round(($attempt->score / $assessment->total_marks) * 100, 1)
            : 0;

        $passed = $assessment->passing_marks 
            ? $attempt->score >= $assessment->passing_marks
            : false;

        // Process detailed responses
        $detailedResponses = $attempt->responses->map(function ($response) {
            $question = $response->question;
            $correctOptions = $question->options->where('is_correct', true);
            
            return [
                'question_id' => $question->question_id,
                'question_text' => $question->question_text,
                'your_answer' => $response->selected_options 
                    ? $question->options->whereIn('option_id', $response->selected_options)->pluck('option_text')->toArray()
                    : [$response->text_answer],
                'correct_answer' => $correctOptions->pluck('option_text')->toArray(),
                'is_correct' => $response->is_correct,
                'marks_awarded' => $response->marks_awarded,
                'marks_possible' => $question->marks
            ];
        });

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->attempt_id,
                'assessment_title' => $assessment->title,
                'score' => $attempt->score,
                'total_marks' => $assessment->total_marks,
                'percentage' => $percentage,
                'passed' => $passed,
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'completed_at' => $attempt->completed_at,
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'questions_summary' => [
                    'total' => $attempt->total_questions,
                    'correct' => $attempt->correct_answers,
                    'incorrect' => $attempt->total_questions - $attempt->correct_answers,
                    'unanswered' => 0 // Can be calculated if needed
                ],
                'detailed_responses' => $detailedResponses
            ]
        ]);
    }
}
