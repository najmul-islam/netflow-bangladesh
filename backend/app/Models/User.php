<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'username', 
        'password_hash',
        'first_name',
        'last_name',
        'phone',
        'avatar_url',
        'bio',
        'timezone',
        'language',
        'status',
        'email_verified',
        'last_login'
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    // Accessors & Mutators
    public function getPasswordAttribute()
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = $value;
    }

    protected $casts = [
        'email_verified' => 'boolean',
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withPivot('assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    public function batchEnrollments()
    {
        return $this->hasMany(BatchEnrollment::class, 'user_id');
    }

    public function instructorBatches()
    {
        return $this->belongsToMany(CourseBatch::class, 'batch_instructors', 'user_id', 'batch_id')
                    ->withPivot('role', 'assigned_at', 'assigned_by', 'is_active');
    }

    public function createdCourses()
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function enrollments()
    {
        return $this->hasMany(BatchEnrollment::class, 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    public function certificates()
    {
        return $this->hasMany(BatchCertificate::class, 'user_id');
    }

    public function lessonProgress()
    {
        return $this->hasMany(BatchLessonProgress::class, 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(BatchReview::class, 'user_id');
    }

    // Helper methods
    public function hasRole($roleName)
    {
        return $this->roles()->where('role_name', $roleName)->exists();
    }

    public function hasPermission($permissionName)
    {
        return $this->roles()->whereHas('permissions', function($q) use ($permissionName) {
            $q->where('permission_name', $permissionName);
        })->exists();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}