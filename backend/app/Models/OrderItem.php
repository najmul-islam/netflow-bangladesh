<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Helper methods
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    public function getFormattedUnitPriceAttribute()
    {
        return '$' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute()
    {
        return '$' . number_format($this->getTotalPriceAttribute(), 2);
    }

    public function calculateTotal()
    {
        return $this->quantity * $this->unit_price;
    }

    public function updateQuantity($newQuantity)
    {
        $this->update(['quantity' => $newQuantity]);
        
        // Update order total
        $this->order->update([
            'total_amount' => $this->order->calculateTotal()
        ]);
    }

    public function canIncreaseQuantity($amount = 1)
    {
        return $this->product->hasStock($amount);
    }

    public function increaseQuantity($amount = 1)
    {
        if ($this->canIncreaseQuantity($amount)) {
            $this->increment('quantity', $amount);
            return true;
        }
        return false;
    }

    public function decreaseQuantity($amount = 1)
    {
        if ($this->quantity > $amount) {
            $this->decrement('quantity', $amount);
            return true;
        } elseif ($this->quantity == $amount) {
            $this->delete();
            return true;
        }
        return false;
    }

    public function isValidQuantity()
    {
        return $this->quantity > 0 && $this->product->hasStock($this->quantity);
    }

    public function getSubtotal()
    {
        return $this->quantity * $this->unit_price;
    }
}