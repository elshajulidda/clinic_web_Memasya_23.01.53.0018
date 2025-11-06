
<?php
include_once __DIR__ . '/../config/database.php';
handleCors();

sendJsonResponse([
    "success" => true,
    "message" => "API is working",
    "timestamp" => date('Y-m-d H:i:s'),
    "data" => [
        "patients" => "patients.php",
        "doctors" => "doctors.php", 
        "appointments" => "appointments.php",
        "medicines" => "medicines.php",
        "invoices" => "invoices.php"
    ]
]);
?>
