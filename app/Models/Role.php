<?php

namespace App\Models;

use MongoDB\BSON\ObjectId;

class Role extends Model
{
    protected $collectionName = 'roles';
    protected $fillable = [
        'name',
        'permissions',
        'description',
        'is_active'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationship with users
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
