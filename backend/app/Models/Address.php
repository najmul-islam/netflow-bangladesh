<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Address extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'addresses';
    protected $primaryKey = 'address_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'email',
        'line1',
        'line2',
        'city',
        'state',
        'postal_code',
        'country',
        'type'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helper methods
    public function isShippingAddress()
    {
        return $this->type === 'shipping';
    }

    public function isBillingAddress()
    {
        return $this->type === 'billing';
    }

    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->line1,
            $this->line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    public function hasContactInfo()
    {
        return !empty($this->phone) || !empty($this->email);
    }
}