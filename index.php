<?php
// index.php - main entry point

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins for dev (update for production)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request (CORS)
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
	http_response_code(200);
	exit();
}

// Load Composer autoload for PHPMailer/JWT, etc.
require_once __DIR__ . '/vendor/autoload.php';

$method = $_SERVER["REQUEST_METHOD"];
$queryString = $_SERVER["QUERY_STRING"] ?? '';

$uri = $_SERVER["REQUEST_URI"];

// Call router
$response = handleRequest($uri, $method);

// Output JSON response
echo $response;

/**
 * Main router function
 */
function handleRequest($url, $method) {
	$url = trim($url, '/'); // remove leading/trailing slashes
	$parts = explode('/', $url);

	
	$dir = "routes/";

	if (count($parts) >= 3) {
		$path = $parts[2];   // e.g., 'auth'
		// Support nested routes beyond a single segment, e.g., 'users/me/avatar' or 'books/12/reviews'
		$route = implode('/', array_slice($parts, 3));

		// full route file path
		$fullpath = __DIR__ . "/routes/{$path}.route.php";

		if (is_file($fullpath)) {
			require_once $fullpath;

			// function name convention e.g., authRoutes(), booksRoutes()
			$function = $path . "Routes";

			if (function_exists($function)) {
				// Parse request body for POST/PUT, otherwise GET params
				$data = in_array($method, ["POST", "PUT", "PATCH"]) 
					? json_decode(file_get_contents("php://input"), true)
					: $_GET;

				// Store parsed data globally so controllers can access without re-reading php://input
				$GLOBALS['__ROUTER_JSON__'] = is_array($data) ? $data : [];

				// Call route handler
				$response = $function($route, $data, $method);
			} else {
				$response = json_encode([
					"statuscode" => -1,
					"status" => "Invalid route function"
				]);
			}
		} else {
			$response = json_encode([
				"statuscode" => -1,
				"status" => "Invalid path: {$path}"
			]);
		}
	} else {
		$response = json_encode([
			"statuscode" => -1,
			"status" => "Invalid request format"
		]);
	}

	return $response;
}
