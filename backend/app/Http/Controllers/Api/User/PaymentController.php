<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group User API - Payments
 * 
 * Endpoints for managing payments and course purchases (requires authentication)
 */
class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user payment history
     * 
     * Get all payments made by the authenticated user.
     * 
     * @authenticated
     * @queryParam status string Filter by payment status (initiated,processing,completed,failed,refunded). Example: completed
     * @queryParam method string Filter by payment method (card,mobile_banking,bank_transfer). Example: card
     * @queryParam per_page integer Number of payments per page (max 50). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "payment_id": "uuid-string",
     *       "amount": 5000.00,
     *       "currency": "BDT",
     *       "method": "card",
     *       "transaction_id": "TXN123456789",
     *       "status": "completed",
     *       "paid_at": "2024-01-20T14:30:00Z",
     *       "orders": [
     *         {
     *           "order_id": "uuid-string",
     *           "total_amount": 5000.00,
     *           "status": "confirmed",
     *           "items": [
     *             {
     *               "product_id": "uuid-string",
     *               "product_title": "Web Development Course",
     *               "quantity": 1,
     *               "unit_price": 5000.00,
     *               "total_price": 5000.00
     *             }
     *           ]
     *         }
     *       ]
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 15,
     *     "last_page": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:initiated,processing,completed,failed,refunded',
            'method' => 'nullable|in:card,mobile_banking,bank_transfer',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        $perPage = $request->get('per_page', 20);

        $query = Payment::with([
            'orders.orderItems.product:product_id,title,price'
        ])
        ->where('user_id', auth()->user()->user_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage()
            ]
        ]);
    }

    /**
     * Initiate course payment
     * 
     * Start a new payment for course enrollment.
     * 
     * @authenticated
     * @bodyParam batch_id string required Batch ID to enroll in.
     * @bodyParam payment_method string required Payment method (card,mobile_banking,bank_transfer).
     * @bodyParam return_url string Return URL after payment completion.
     * @bodyParam cancel_url string Cancel URL if payment is cancelled.
     * 
     * @response 201 {
     *   "data": {
     *     "payment_id": "uuid-string",
     *     "order_id": "uuid-string",
     *     "amount": 5000.00,
     *     "currency": "BDT",
     *     "method": "card",
     *     "status": "initiated",
     *     "payment_url": "https://payment-gateway.com/pay/uuid-string",
     *     "expires_at": "2024-01-20T15:00:00Z",
     *     "batch": {
     *       "batch_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "course_title": "Web Development Fundamentals",
     *       "price": 5000.00
     *     }
     *   },
     *   "message": "Payment initiated successfully"
     * }
     * 
     * @response 400 {
     *   "message": "Cannot initiate payment",
     *   "errors": ["Batch is full", "Already enrolled", "Batch not available"]
     * }
     */
    public function initiateCoursePayment(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:course_batches,batch_id',
            'payment_method' => 'required|in:card,mobile_banking,bank_transfer',
            'return_url' => 'nullable|url',
            'cancel_url' => 'nullable|url'
        ]);

        $batch = CourseBatch::with('course')
            ->where('batch_id', $request->batch_id)
            ->where('status', 'active')
            ->first();

        if (!$batch) {
            return response()->json([
                'message' => 'Cannot initiate payment',
                'errors' => ['Batch not available']
            ], 400);
        }

        // Check if user is already enrolled
        $existingEnrollment = auth()->user()->enrollments()
            ->where('batch_id', $request->batch_id)
            ->whereIn('status', ['active', 'pending'])
            ->exists();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Cannot initiate payment',
                'errors' => ['Already enrolled in this batch']
            ], 400);
        }

        // Check batch capacity
        $currentEnrollments = $batch->enrollments()
            ->whereIn('status', ['active', 'pending'])
            ->count();

        if ($batch->max_students && $currentEnrollments >= $batch->max_students) {
            return response()->json([
                'message' => 'Cannot initiate payment',
                'errors' => ['Batch is full']
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => auth()->user()->user_id,
                'total_amount' => $batch->course->price,
                'status' => 'pending',
                'payment_status' => 'unpaid'
            ]);

            // Create order item (assuming course as product)
            $order->orderItems()->create([
                'product_id' => $batch->course->course_id, // Assuming course_id as product_id
                'quantity' => 1,
                'unit_price' => $batch->course->price,
                'total_price' => $batch->course->price
            ]);

            // Create payment
            $payment = Payment::create([
                'user_id' => auth()->user()->user_id,
                'amount' => $batch->course->price,
                'currency' => 'BDT',
                'method' => $request->payment_method,
                'status' => 'initiated',
                'metadata' => [
                    'batch_id' => $request->batch_id,
                    'return_url' => $request->return_url,
                    'cancel_url' => $request->cancel_url
                ]
            ]);

            // Update order with payment
            $order->update(['payment_id' => $payment->payment_id]);

            // Generate payment URL (integrate with your payment gateway)
            $paymentUrl = $this->generatePaymentUrl($payment);

            DB::commit();

            return response()->json([
                'data' => [
                    'payment_id' => $payment->payment_id,
                    'order_id' => $order->order_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'method' => $payment->method,
                    'status' => $payment->status,
                    'payment_url' => $paymentUrl,
                    'expires_at' => now()->addMinutes(30), // Payment expires in 30 minutes
                    'batch' => [
                        'batch_id' => $batch->batch_id,
                        'batch_name' => $batch->batch_name,
                        'course_title' => $batch->course->title,
                        'price' => $batch->course->price
                    ]
                ],
                'message' => 'Payment initiated successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to initiate payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Payment callback/webhook
     * 
     * Handle payment gateway callback to update payment status.
     * 
     * @bodyParam payment_id string required Payment ID.
     * @bodyParam transaction_id string required Transaction ID from gateway.
     * @bodyParam status string required Payment status (completed,failed).
     * @bodyParam signature string required Payment gateway signature for verification.
     * 
     * @response 200 {
     *   "message": "Payment processed successfully",
     *   "data": {
     *     "payment_id": "uuid-string",
     *     "status": "completed",
     *     "enrollment_status": "active"
     *   }
     * }
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,payment_id',
            'transaction_id' => 'required|string',
            'status' => 'required|in:completed,failed',
            'signature' => 'required|string'
        ]);

        // Verify signature (implement based on your payment gateway)
        if (!$this->verifySignature($request->all())) {
            return response()->json([
                'message' => 'Invalid signature'
            ], 400);
        }

        $payment = Payment::where('payment_id', $request->payment_id)->first();

        if (!$payment || $payment->status !== 'initiated') {
            return response()->json([
                'message' => 'Invalid payment or already processed'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update payment
            $payment->update([
                'status' => $request->status,
                'transaction_id' => $request->transaction_id,
                'paid_at' => $request->status === 'completed' ? now() : null
            ]);

            // Update order
            $order = $payment->orders()->first();
            if ($order) {
                $order->update([
                    'payment_status' => $request->status === 'completed' ? 'paid' : 'failed',
                    'status' => $request->status === 'completed' ? 'confirmed' : 'cancelled'
                ]);
            }

            $enrollmentStatus = null;

            // Create enrollment if payment successful
            if ($request->status === 'completed' && isset($payment->metadata['batch_id'])) {
                $enrollment = auth()->user()->enrollments()->create([
                    'batch_id' => $payment->metadata['batch_id'],
                    'enrollment_date' => now(),
                    'status' => 'active',
                    'payment_id' => $payment->payment_id
                ]);

                $enrollmentStatus = $enrollment->status;
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment processed successfully',
                'data' => [
                    'payment_id' => $payment->payment_id,
                    'status' => $payment->status,
                    'enrollment_status' => $enrollmentStatus
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details
     * 
     * Get details of a specific payment.
     * 
     * @authenticated
     * @urlParam payment_id string required The payment ID. Example: "uuid-string"
     * 
     * @response 200 {
     *   "data": {
     *     "payment_id": "uuid-string",
     *     "amount": 5000.00,
     *     "currency": "BDT",
     *     "method": "card",
     *     "transaction_id": "TXN123456789",
     *     "status": "completed",
     *     "paid_at": "2024-01-20T14:30:00Z",
     *     "created_at": "2024-01-20T14:25:00Z",
     *     "order": {
     *       "order_id": "uuid-string",
     *       "total_amount": 5000.00,
     *       "status": "confirmed",
     *       "items": [
     *         {
     *           "product_title": "Web Development Course",
     *           "quantity": 1,
     *           "unit_price": 5000.00
     *         }
     *       ]
     *     },
     *     "enrollment": {
     *       "enrollment_id": "uuid-string",
     *       "batch_name": "Web Dev Batch 2024-A",
     *       "status": "active"
     *     }
     *   }
     * }
     */
    public function show(string $payment_id): JsonResponse
    {
        $payment = Payment::with([
            'orders.orderItems.product:product_id,title',
            'user.enrollments' => function ($query) use ($payment_id) {
                $query->where('payment_id', $payment_id);
            },
            'user.enrollments.batch:batch_id,batch_name'
        ])
        ->where('payment_id', $payment_id)
        ->where('user_id', auth()->user()->user_id)
        ->firstOrFail();

        $enrollment = $payment->user->enrollments->first();

        return response()->json([
            'data' => [
                'payment_id' => $payment->payment_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
                'created_at' => $payment->created_at,
                'order' => $payment->orders->first() ? [
                    'order_id' => $payment->orders->first()->order_id,
                    'total_amount' => $payment->orders->first()->total_amount,
                    'status' => $payment->orders->first()->status,
                    'items' => $payment->orders->first()->orderItems->map(function ($item) {
                        return [
                            'product_title' => $item->product->title ?? 'Course',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price
                        ];
                    })
                ] : null,
                'enrollment' => $enrollment ? [
                    'enrollment_id' => $enrollment->enrollment_id,
                    'batch_name' => $enrollment->batch->batch_name,
                    'status' => $enrollment->status
                ] : null
            ]
        ]);
    }

    /**
     * Generate payment URL for gateway integration
     */
    private function generatePaymentUrl(Payment $payment): string
    {
        // Implement your payment gateway integration here
        // This is a placeholder implementation
        return config('app.url') . '/payment/gateway/' . $payment->payment_id;
    }

    /**
     * Verify payment gateway signature
     */
    private function verifySignature(array $data): bool
    {
        // Implement signature verification based on your payment gateway
        // This is a placeholder implementation
        return true;
    }
}
