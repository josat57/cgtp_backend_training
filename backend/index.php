<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");


$method = $_SERVER["REQUEST_METHOD"];
$queryString = $_SERVER["QUERY_STRING"] ?? '';

$uri = $_SERVER["REQUEST_URI"];
$response = handleRequest($uri, $method);

echo json_encode($response);
// http://localhost/bba/backend/index.php/auth/register
function handleRequest($url, $method) {
    $url = trim($url, '/');
    $parts = explode('/', $url);
    $dir = "routes/";
    if (count($parts)>=3) {
        $path = $parts[3];
        $route = $parts[4];
        chdir(__DIR__."/routes/");
        $fullpath = __DIR__."/routes/{$path}.route.php";
        if (is_file($fullpath)) {
            require_once $fullpath;
            $function = $path."Routes";
            if (function_exists($function)) {
                if ($method == "POST") {
                    $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
                    if (stripos($contentType, 'application/json') !== false) {
                        $raw = file_get_contents('php://input');
                        $decoded = json_decode($raw, true);
                        $data = is_array($decoded) ? $decoded : [];
                    } else {
                        $data = $_POST;
                    }
                } else {
                    $data = $_GET;
                }
                // Support Authorization: Bearer <token>
                $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                if (!$auth && function_exists('apache_request_headers')) {
                    $headers = apache_request_headers();
                    $auth = $headers['Authorization'] ?? '';
                }
                if (stripos($auth, 'Bearer ') === 0) {
                    $token = trim(substr($auth, 7));
                    if (!isset($data['jwt'])) { $data['jwt'] = $token; }
                }
                $response = $function($route, $data, $path);
            } else {
                $response = json_encode(["statuscode"=>-1, "status"=>"Invalid function path"]);
            }
        } else {
            $response = json_encode(["statuscode"=>-1, "status"=>"Invalid  file path $path"]);
        }
    } else {
        $response = json_encode(["statuscode"=>-1, "status"=>"Invalid path parts"]);
    }
    return $response;
}