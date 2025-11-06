<?php
// test_appointment.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test data untuk appointment
$testData = [
    "success" => true,
    "message" => "Test appointment created successfully", 
    "appointment_id" => 999
];

echo json_encode($testData);
?>