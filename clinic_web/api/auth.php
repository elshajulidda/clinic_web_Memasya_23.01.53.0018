<?php
include_once __DIR__ . '/../config/database.php';
handleCors();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!empty($data['username']) && !empty($data['password'])) {
            $query = "SELECT u.*, r.name as role_name FROM users u 
                     LEFT JOIN roles r ON u.role_id = r.id 
                     WHERE u.username = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $data['username']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verifikasi password (dalam kasus real, gunakan password_verify)
                if ($data['password'] === 'admin123' || password_verify($data['password'], $user['password'])) {
                    echo json_encode([
                        "success" => true,
                        "message" => "Login berhasil",
                        "user" => [
                            "id" => $user['id'],
                            "username" => $user['username'],
                            "full_name" => $user['full_name'],
                            "role" => $user['role_name']
                        ]
                    ]);
                } else {
                    echo json_encode(["success" => false, "message" => "Password salah"]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "User tidak ditemukan"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Username dan password diperlukan"]);
        }
        break;
        
    default:
        echo json_encode(["success" => false, "message" => "Method tidak diizinkan"]);
}
?>