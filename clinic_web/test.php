<?php
// test_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test koneksi database
$host = "127.0.0.1";
$db_name = "clinic_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query patients
    $stmt = $conn->query("SELECT COUNT(*) as total FROM patients");
    $patients_count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test query doctors
    $stmt = $conn->query("SELECT COUNT(*) as total FROM doctors");
    $doctors_count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test query appointments
    $stmt = $conn->query("SELECT COUNT(*) as total FROM appointments");
    $appointments_count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test query medicines
    $stmt = $conn->query("SELECT COUNT(*) as total FROM medicines");
    $medicines_count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "message" => "Database connection successful",
        "data" => [
            "patients" => $patients_count['total'],
            "doctors" => $doctors_count['total'],
            "appointments" => $appointments_count['total'],
            "medicines" => $medicines_count['total']
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
}
?>