<?php

namespace App\Models;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Book extends Model
{
    protected $collectionName = 'books';
    
    protected $fillable = [
        'title',
        'author_id',
        'isbn',
        'description',
        'published_date',
        'publisher',
        'language',
        'page_count',
        'categories',
        'cover_image',
        'status',
        'price',
        'stock_quantity'
    ];

    protected $casts = [
        'published_date' => 'datetime',
        'page_count' => 'integer',
        'price' => 'float',
        'stock_quantity' => 'integer',
        'categories' => 'array'
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Custom methods
    public function isAvailable()
    {
        return $this->stock_quantity > 0 && $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', new ObjectId($authorId));
    }

    public function getAverageRating()
    {
        // Implementation depends on your review system
        // This is a placeholder
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewCount()
    {
        return $this->reviews()->count();
    }
}
