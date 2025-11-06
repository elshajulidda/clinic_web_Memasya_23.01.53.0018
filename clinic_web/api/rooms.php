<?php
include_once '../config/database.php';
handleCors();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $query = "SELECT r.*, c.name as clinic_name 
                 FROM rooms r 
                 LEFT JOIN clinics c ON r.clinic_id = c.id 
                 ORDER BY r.name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $rooms = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rooms[] = $row;
        }
        
        echo json_encode($rooms);
        break;
        
    default:
        echo json_encode(["success" => false, "message" => "Method tidak diizinkan"]);
}
?>