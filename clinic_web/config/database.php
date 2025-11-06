<?php
class Database {
    private $host = "127.0.0.1";
    private $db_name = "clinic_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            // Return null instead of throwing to prevent fatal errors
            return null;
        }
        return $this->conn;
    }
}

function handleCors() {
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        http_response_code(200);
        exit();
    }
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    // Ensure we're always sending valid JSON
    if ($data === null) {
        echo json_encode(["success" => false, "message" => "No data"]);
    } elseif (is_array($data) || is_object($data)) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

function getRequestData() {
    $input = file_get_contents("php://input");
    
    if (empty($input)) {
        return [];
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        sendJsonResponse([
            "success" => false,
            "message" => "Invalid JSON data: " . json_last_error_msg()
        ], 400);
    }
    
    return $data;
}

// Global error handler
function handleShutdown() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Shutdown error: " . print_r($error, true));
        sendJsonResponse([
            "success" => false,
            "message" => "Internal server error"
        ], 500);
    }
}

register_shutdown_function('handleShutdown');

// Set error handler untuk menangkap semua error
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    sendJsonResponse([
        "success" => false,
        "message" => "PHP Error: $errstr"
    ], 500);
    return true;
}, E_ALL);
?>
