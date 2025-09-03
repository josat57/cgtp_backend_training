<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Utils\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuditController
{
    private $auditLogModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLog();
    }

    public function listLogs(Request $request, $response, $args)
    {
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
        $filters = [];

        if (isset($queryParams['action'])) {
            $filters['action'] = $queryParams['action'];
        }
        if (isset($queryParams['user_id'])) {
            $filters['user_id'] = new \MongoDB\BSON\ObjectId($queryParams['user_id']);
        }
        if (isset($queryParams['start_date']) && isset($queryParams['end_date'])) {
            $filters['created_at'] = [
                '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime($queryParams['start_date']) * 1000),
                '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime($queryParams['end_date']) * 1000)
            ];
        }

        $logs = $this->auditLogModel->getLogs($page, $limit, $filters);
        $total = $this->auditLogModel->countLogs($filters);

        return Response::success([
            'logs' => $logs,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function getLog(Request $request, $response, $args)
    {
        $logId = $args['id'];
        
        try {
            $log = $this->auditLogModel->getLogById($logId);
            
            if (!$log) {
                return Response::error('Log not found', 404);
            }
            
            return Response::success($log);
        } catch (\Exception $e) {
            return Response::error('Invalid log ID', 400);
        }
    }

    public function getUserLogs(Request $request, $response, $args)
    {
        $userId = $args['userId'];
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;

        try {
            $logs = $this->auditLogModel->getLogsByUserId($userId, $page, $limit);
            $total = $this->auditLogModel->countLogs(['user_id' => new \MongoDB\BSON\ObjectId($userId)]);

            return Response::success([
                'logs' => $logs,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        } catch (\Exception $e) {
            return Response::error('Invalid user ID', 400);
        }
    }

    public static function logAction($userId, $action, $entityType = null, $entityId = null, $details = [])
    {
        $auditLog = new AuditLog();
        $auditLog->log([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => new \MongoDB\BSON\UTCDateTime()
        ]);
    }
}