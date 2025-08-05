<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'category_id';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_category_id',
        'icon',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_category_id');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function hasParent()
    {
        return !is_null($this->parent_category_id);
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function getFullNameAttribute()
    {
        if ($this->hasParent()) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getPublishedCoursesCount()
    {
        return $this->courses()->where('status', 'published')->count();
    }
}