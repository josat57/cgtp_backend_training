<?php

namespace App\Utils;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public static function json(ResponseInterface $response, $data = null, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function success($data = null, int $status = 200, array $headers = [])
    {
        $response = new \Slim\Psr7\Response($status);
        
        $responseData = ['success' => true];
        if ($data !== null) {
            $responseData['data'] = $data;
        }
        
        $response->getBody()->write(json_encode($responseData));
        
        $response = $response->withHeader('Content-Type', 'application/json');
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response->withStatus($status);
    }
    
    public static function error(string $message, int $status = 400, array $details = [])
    {
        $response = new \Slim\Psr7\Response($status);
        
        $error = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ]
        ];
        
        if (!empty($details)) {
            $error['error']['details'] = $details;
        }
        
        $response->getBody()->write(json_encode($error));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
