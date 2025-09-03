<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Utils\Response as ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuditLogController
{
    private $auditLog;
    private $user;

    public function __construct()
    {
        $this->auditLog = new AuditLog();
        $this->user = new User();
    }

    // Get all audit logs with filters
    public function index(Request $request, Response $response, array $args)
    {
        $query = $request->getQueryParams();
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 20;
        
        // Build filters
        $filters = [];
        
        if (!empty($query['user_id'])) {
            $filters['user_id'] = $query['user_id'];
        }
        
        if (!empty($query['action'])) {
            $filters['action'] = $query['action'];
        }
        
        if (!empty($query['model_type'])) {
            $filters['model_type'] = $query['model_type'];
        }
        
        if (!empty($query['model_id'])) {
            $filters['model_id'] = $query['model_id'];
        }
        
        if (!empty($query['start_date']) && !empty($query['end_date'])) {
            $filters['created_at'] = [
                '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime($query['start_date']) * 1000),
                '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime($query['end_date'] . ' 23:59:59') * 1000)
            ];
        }
        
        // Get logs with pagination
        $logs = $this->auditLog->where($filters)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
            
        return ApiResponse::success($logs);
    }

    // Get audit log by ID
    public function show(Request $request, Response $response, array $args)
    {
        $log = $this->auditLog->with(['user'])->find($args['id']);
        
        if (!$log) {
            return ApiResponse::error('Audit log not found', 404);
        }
        
        return ApiResponse::success($log);
    }

    // Get audit logs for a specific user
    public function getUserLogs(Request $request, Response $response, array $args)
    {
        $userId = $args['userId'];
        $query = $request->getQueryParams();
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 20;
        
        // Check if user exists
        $user = $this->user->find($userId);
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }
        
        // Build filters
        $filters = ['user_id' => $userId];
        
        if (!empty($query['action'])) {
            $filters['action'] = $query['action'];
        }
        
        if (!empty($query['start_date']) && !empty($query['end_date'])) {
            $filters['created_at'] = [
                '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime($query['start_date']) * 1000),
                '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime($query['end_date'] . ' 23:59:59') * 1000)
            ];
        }
        
        // Get logs with pagination
        $logs = $this->auditLog->where($filters)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
            
        return ApiResponse::success($logs);
    }

    // Get audit logs for a specific model
    public function getModelLogs(Request $request, Response $response, array $args)
    {
        $modelType = $args['modelType'];
        $modelId = $args['modelId'] ?? null;
        $query = $request->getQueryParams();
        $page = $query['page'] ?? 1;
        $limit = $query['limit'] ?? 20;
        
        // Build filters
        $filters = ['model_type' => $modelType];
        
        if ($modelId) {
            $filters['model_id'] = $modelId;
        }
        
        if (!empty($query['action'])) {
            $filters['action'] = $query['action'];
        }
        
        if (!empty($query['start_date']) && !empty($query['end_date'])) {
            $filters['created_at'] = [
                '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime($query['start_date']) * 1000),
                '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime($query['end_date'] . ' 23:59:59') * 1000)
            ];
        }
        
        // Get logs with pagination
        $logs = $this->auditLog->where($filters)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
            
        return ApiResponse::success($logs);
    }

    // Get available actions for filtering
    public function getActions(Request $request, Response $response, array $args)
    {
        $actions = $this->auditLog->distinct('action');
        return ApiResponse::success($actions);
    }

    // Get available model types for filtering
    public function getModelTypes(Request $request, Response $response, array $args)
    {
        $modelTypes = $this->auditLog->distinct('model_type');
        return ApiResponse::success($modelTypes);
    }
}
