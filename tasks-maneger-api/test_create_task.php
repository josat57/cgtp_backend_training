<?php

// Test script to demonstrate the complete workflow for creating a task

// Configuration
$apiUrl = 'http://localhost:8000';

// Step 1: Register a new user
echo "Step 1: Registering a new user...\n";
$userData = [
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com', // Use timestamp to ensure unique email
    'password' => 'password123'
];

$registerResponse = makeRequest($apiUrl . '/register', 'POST', $userData);
echo "Register Response: " . json_encode($registerResponse, JSON_PRETTY_PRINT) . "\n\n";

if ($registerResponse['status'] !== 'success') {
    die("Registration failed. Exiting.\n");
}

// Step 2: Login to get JWT token
echo "Step 2: Logging in to get JWT token...\n";
$loginData = [
    'email' => $userData['email'],
    'password' => $userData['password']
];

$loginResponse = makeRequest($apiUrl . '/login', 'POST', $loginData);
echo "Login Response: " . json_encode($loginResponse, JSON_PRETTY_PRINT) . "\n\n";

if ($loginResponse['status'] !== 'success' || !isset($loginResponse['data']['token'])) {
    die("Login failed or token not received. Exiting.\n");
}

$token = $loginResponse['data']['token'];

// Step 3: Create a task using the token
echo "Step 3: Creating a task...\n";
$taskData = [
    'title' => 'Test Task ' . time(),
    'description' => 'This is a test task created at ' . date('Y-m-d H:i:s'),
    'status' => 'pending',
    'priority' => 'high',
    'due_date' => date('Y-m-d', strtotime('+7 days'))
];

// Test both API routes
echo "Creating task using /api/task endpoint:\n";
$createTaskResponse = makeRequest($apiUrl . '/api/task', 'POST', $taskData, $token);
echo "Create Task Response: " . json_encode($createTaskResponse, JSON_PRETTY_PRINT) . "\n\n";

echo "Test completed.\n";

/**
 * Helper function to make HTTP requests
 * 
 * @param string $url The URL to request
 * @param string $method HTTP method (GET, POST, etc.)
 * @param array $data Request data
 * @param string $token JWT token for authentication (optional)
 * @return array Response data
 */
function makeRequest($url, $method = 'GET', $data = [], $token = null) {
    $ch = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ];
    
    // Add authorization header if token is provided
    if ($token) {
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $token;
    }
    
    // Set method and data
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    } else if ($method !== 'GET') {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'error', 'message' => 'cURL Error: ' . $error];
    }
    
    return json_decode($response, true) ?: ['status' => 'error', 'message' => 'Invalid response: ' . $response];
}