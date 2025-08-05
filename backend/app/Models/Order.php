<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'shipping_address_id',
        'billing_address_id',
        'total_amount',
        'status',
        'payment_status',
        'payment_id'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items', 'order_id', 'product_id')
                    ->withPivot('quantity', 'unit_price', 'total_price');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isShipped()
    {
        return $this->status === 'shipped';
    }

    public function isDelivered()
    {
        return $this->status === 'delivered';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function isUnpaid()
    {
        return $this->payment_status === 'unpaid';
    }

    public function isRefunded()
    {
        return $this->payment_status === 'refunded';
    }

    public function hasFailed()
    {
        return $this->payment_status === 'failed';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeShipped()
    {
        return $this->status === 'processing' && $this->isPaid();
    }

    public function getItemsCount()
    {
        return $this->orderItems()->sum('quantity');
    }

    public function getFormattedTotalAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    public function calculateTotal()
    {
        return $this->orderItems()->sum('total_price');
    }

    public function updateStatus($newStatus)
    {
        $this->update(['status' => $newStatus]);
    }

    public function updatePaymentStatus($newPaymentStatus)
    {
        $this->update(['payment_status' => $newPaymentStatus]);
    }

    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    public function getPaymentStatusDisplayAttribute()
    {
        return match($this->payment_status) {
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            default => ucfirst($this->payment_status)
        };
    }
}