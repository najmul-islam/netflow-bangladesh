<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'method',
        'transaction_id',
        'status',
        'metadata',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'json',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_id');
    }

    // Helper methods
    public function isInitiated()
    {
        return $this->status === 'initiated';
    }

    public function isSuccess()
    {
        return $this->status === 'success';
    }

    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    public function isRefunded()
    {
        return $this->status === 'refunded';
    }

    public function isPending()
    {
        return $this->status === 'initiated';
    }

    public function isCompleted()
    {
        return $this->status === 'success';
    }

    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'initiated' => 'Initiated',
            'success' => 'Success',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($this->status)
        };
    }

    public function markAsSuccess($transactionId = null)
    {
        $updateData = [
            'status' => 'success',
            'paid_at' => now()
        ];

        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        $this->update($updateData);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function markAsRefunded()
    {
        $this->update(['status' => 'refunded']);
    }

    public function getMetadataValue($key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function setMetadataValue($key, $value)
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
    }

    public function getPaymentMethodDisplayAttribute()
    {
        return match($this->method) {
            'card' => 'Credit/Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'razorpay' => 'Razorpay',
            'cash' => 'Cash Payment',
            default => ucfirst(str_replace('_', ' ', $this->method))
        };
    }

    public function canBeRefunded()
    {
        return $this->isSuccess() && $this->paid_at && $this->paid_at->diffInDays(now()) <= 30;
    }

    public function getTransactionReference()
    {
        return $this->transaction_id ?? $this->payment_id;
    }
}