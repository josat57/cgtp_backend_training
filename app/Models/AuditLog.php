<?php

namespace App\Models;

use MongoDB\BSON\ObjectId;

class AuditLog extends Model
{
    protected $collectionName = 'audit_logs';
    
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'method',
        'route_name',
        'status_code'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime'
    ];

    // Action types
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_ACCESS = 'access';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', new ObjectId($userId));
    }

    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', new ObjectId($modelId));
        }
        
        return $query;
    }

    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeBetweenDates($query, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: now();
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Helper methods
    public static function log(
        $userId, 
        string $action, 
        string $modelType = null, 
        $modelId = null, 
        array $oldValues = null, 
        array $newValues = null,
        array $additionalData = []
    ) {
        $request = request();
        
        return static::create(array_merge([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'route_name' => $request->route() ? $request->route()->getName() : null,
            'status_code' => http_response_code()
        ], $additionalData));
    }
}
