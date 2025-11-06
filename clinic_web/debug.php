<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== Clinic API Testing ===\n\n";

// Test configuration
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$api_base = $base_url . '/api/';

echo "Base URL: $base_url\n";
echo "API Base: $api_base\n\n";

// Test endpoints
$endpoints = [
    'patients.php',
    'doctors.php', 
    'appointments.php',
    'medicines.php',
    'invoices.php',
    'rooms.php'
];

foreach ($endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    echo "URL: " . $api_base . $endpoint . "\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($api_base . $endpoint, false, $context);
    
    if ($response === FALSE) {
        echo "Status: ✗ Failed to connect\n";
    } else {
        // Get HTTP status code
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $status_code = $matches[1] ?? 'Unknown';
        
        echo "Status: HTTP $status_code\n";
        
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($data)) {
                echo "Data: ✓ Valid JSON (" . count($data) . " records)\n";
                if (count($data) > 0) {
                    echo "Sample: " . json_encode($data[0]) . "\n";
                }
            } else {
                echo "Data: ✓ Valid JSON (Object)\n";
                echo "Content: " . json_encode($data) . "\n";
            }
        } else {
            echo "Data: ✗ Invalid JSON - " . json_last_error_msg() . "\n";
            echo "Raw Response: " . substr($response, 0, 200) . "...\n";
        }
    }
    echo str_repeat("-", 50) . "\n\n";
}

// Test POST request
echo "Testing POST to patients.php:\n";
$test_data = [
    'full_name' => 'Test Patient ' . time(),
    'gender' => 'M',
    'phone' => '081234567890'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($test_data),
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($api_base . 'patients.php', false, $context);
if ($response === FALSE) {
    echo "POST Test: ✗ Failed\n";
} else {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "POST Test: ✓ " . ($data['success'] ? 'Success' : 'Failed') . "\n";
        echo "Message: " . ($data['message'] ?? 'No message') . "\n";
    } else {
        echo "POST Test: ✗ Invalid JSON response\n";
    }
}
?>