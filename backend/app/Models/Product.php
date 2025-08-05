<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'products';
    protected $primaryKey = 'product_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'description',
        'price',
        'stock_quantity',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id')
                    ->withPivot('quantity', 'unit_price', 'total_price');
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isInStock()
    {
        return $this->stock_quantity > 0;
    }

    public function hasStock($quantity = 1)
    {
        return $this->stock_quantity >= $quantity;
    }

    public function reduceStock($quantity)
    {
        if ($this->hasStock($quantity)) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        return false;
    }

    public function increaseStock($quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    public function getTotalSoldQuantity()
    {
        return $this->orderItems()
                    ->whereHas('order', function($q) {
                        $q->whereIn('status', ['delivered', 'shipped']);
                    })
                    ->sum('quantity');
    }

    public function getTotalRevenue()
    {
        return $this->orderItems()
                    ->whereHas('order', function($q) {
                        $q->where('payment_status', 'paid');
                    })
                    ->sum('total_price');
    }

    public function isOutOfStock()
    {
        return $this->stock_quantity <= 0;
    }

    public function isLowStock($threshold = 10)
    {
        return $this->stock_quantity <= $threshold;
    }
}