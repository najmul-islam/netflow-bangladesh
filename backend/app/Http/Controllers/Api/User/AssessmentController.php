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
 * @group User API - Assessments
 * 
 * Endpoints for taking assessments and viewing results (requires authentication)
 */
class AssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get available assessments
     * 
     * Get all assessments for user's enrolled batches.
     * 
     * @authenticated
     * @queryParam batch_id string Filter by batch ID. Example: "uuid-string"
     * @queryParam status string Filter by assessment status (upcoming,active,completed,expired). Example: active
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "assessment_id": "uuid-string",
     *       "title": "HTML & CSS Quiz",
     *       "description": "Test your knowledge of HTML and CSS",
     *       "assessment_type": "quiz",
     *       "total_marks": 50,
     *       "passing_marks": 35,
     *       "duration_minutes": 60,
     *       "start_date": "2024-01-20T09:00:00Z",
     *       "end_date": "2024-01-22T23:59:59Z",
     *       "max_attempts": 3,
     *       "status": "active",
     *       "batch": {
     *         "batch_id": "uuid-string",
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
     * Start assessment attempt
     * 
     * Start a new attempt for an assessment.
     * 
     * @authenticated
     * @urlParam assessment_id string required The assessment ID. Example: "uuid-string"
     * 
     * @response 201 {
     *   "data": {
     *     "attempt_id": "uuid-string",
     *     "assessment_id": "uuid-string",
     *     "started_at": "2024-01-20T14:30:00Z",
     *     "time_limit_minutes": 60,
     *     "expires_at": "2024-01-20T15:30:00Z",
     *     "questions": [
     *       {
     *         "question_id": "uuid-string",
     *         "question_text": "What does HTML stand for?",
     *         "question_type": "multiple_choice",
     *         "marks": 5,
     *         "order_index": 1,
     *         "options": [
     *           {
     *             "option_id": "uuid-string",
     *             "option_text": "HyperText Markup Language",
     *             "option_letter": "A"
     *           },
     *           {
     *             "option_id": "uuid-string",
     *             "option_text": "High Tech Modern Language",
     *             "option_letter": "B"
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
                $query->orderBy('order_index');
            },
            'questions.options' => function ($query) {
                $query->orderBy('option_letter');
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
     * Submit assessment answers
     * 
     * Submit answers for an assessment attempt.
     * 
     * @authenticated
     * @urlParam attempt_id string required The attempt ID. Example: "uuid-string"
     * @bodyParam answers array required Array of question answers.
     * @bodyParam answers.*.question_id string required Question ID.
     * @bodyParam answers.*.selected_options array required Array of selected option IDs.
     * @bodyParam answers.*.text_answer string Text answer for essay questions.
     * 
     * @response 200 {
     *   "data": {
     *     "attempt_id": "uuid-string",
     *     "score": 42,
     *     "total_marks": 50,
     *     "percentage": 84,
     *     "passed": true,
     *     "status": "completed",
     *     "completed_at": "2024-01-20T15:25:00Z",
     *     "time_taken_minutes": 55,
     *     "correct_answers": 8,
     *     "total_questions": 10
     *   },
     *   "message": "Assessment submitted successfully"
     * }
     * 
     * @response 400 {
     *   "message": "Cannot submit assessment",
     *   "errors": ["Time limit exceeded", "Attempt already completed"]
     * }
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
     * Get attempt results
     * 
     * Get detailed results for a completed assessment attempt.
     * 
     * @authenticated
     * @urlParam attempt_id string required The attempt ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "attempt_id": "uuid-string",
     *     "assessment_title": "HTML & CSS Quiz",
     *     "score": 42,
     *     "total_marks": 50,
     *     "percentage": 84,
     *     "passed": true,
     *     "status": "completed",
     *     "started_at": "2024-01-20T14:30:00Z",
     *     "completed_at": "2024-01-20T15:25:00Z",
     *     "time_taken_minutes": 55,
     *     "questions_summary": {
     *       "total": 10,
     *       "correct": 8,
     *       "incorrect": 2,
     *       "unanswered": 0
     *     },
     *     "detailed_responses": [
     *       {
     *         "question_id": "uuid-string",
     *         "question_text": "What does HTML stand for?",
     *         "your_answer": ["HyperText Markup Language"],
     *         "correct_answer": ["HyperText Markup Language"],
     *         "is_correct": true,
     *         "marks_awarded": 5,
     *         "marks_possible": 5
     *       }
     *     ]
     *   }
     * }
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
