<?php
include_once '../config/database.php';
handleCors();

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    sendJsonResponse([
        "success" => false,
        "message" => "Database connection failed"
    ], 500);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            $query = "SELECT * FROM medicines WHERE is_active = 1 ORDER BY name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $medicines = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $medicines[] = [
                    "id" => (int)$row['id'],
                    "code" => $row['code'],
                    "name" => $row['name'],
                    "generic_name" => $row['generic_name'],
                    "category" => $row['category'],
                    "unit" => $row['unit'],
                    "price" => (float)$row['price'],
                    "stock" => (int)$row['stock'],
                    "min_stock" => (int)$row['min_stock'],
                    "supplier" => $row['supplier'],
                    "expiry_date" => $row['expiry_date'],
                    "description" => $row['description'],
                    "is_active" => (bool)$row['is_active'],
                    "created_at" => $row['created_at']
                ];
            }
            
            sendJsonResponse($medicines);
            break;
            
        case 'POST':
            $data = getRequestData();
            
            // Validate required fields
            $required = ['code', 'name', 'unit', 'price'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Field '$field' is required"
                    ], 400);
                }
            }
            
            $query = "INSERT INTO medicines 
                     (code, name, generic_name, category, unit, price, stock, min_stock, supplier, expiry_date, description) 
                     VALUES 
                     (:code, :name, :generic_name, :category, :unit, :price, :stock, :min_stock, :supplier, :expiry_date, :description)";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':code', $data['code']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':generic_name', $data['generic_name'] ?? '');
            $stmt->bindParam(':category', $data['category'] ?? '');
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':stock', $data['stock'] ?? 0);
            $stmt->bindParam(':min_stock', $data['min_stock'] ?? 0);
            $stmt->bindParam(':supplier', $data['supplier'] ?? '');
            $stmt->bindParam(':expiry_date', $data['expiry_date'] ?? null);
            $stmt->bindParam(':description', $data['description'] ?? '');
            
            if ($stmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Medicine created successfully",
                    "medicine_id" => $db->lastInsertId()
                ], 201);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to create medicine"
                ], 500);
            }
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Medicine ID is required"
                ], 400);
            }
            
            $data = getRequestData();
            
            // Check if medicine exists
            $checkQuery = "SELECT id FROM medicines WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Medicine not found"
                ], 404);
            }
            
            // Build update query
            $updateFields = [];
            $params = [':id' => $id];
            
            $allowedFields = ['code', 'name', 'generic_name', 'category', 'unit', 'price', 'stock', 'min_stock', 'supplier', 'expiry_date', 'description', 'is_active'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "No fields to update"
                ], 400);
            }
            
            $updateQuery = "UPDATE medicines SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            
            foreach ($params as $key => $value) {
                $updateStmt->bindValue($key, $value);
            }
            
            if ($updateStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Medicine updated successfully"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to update medicine"
                ], 500);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Medicine ID is required"
                ], 400);
            }
            
            // Soft delete - set is_active to 0
            $query = "UPDATE medicines SET is_active = 0 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Medicine deleted successfully"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to delete medicine"
                ], 500);
            }
            break;
            
        default:
            sendJsonResponse([
                "success" => false,
                "message" => "Method not allowed"
            ], 405);
    }
    
} catch (Exception $e) {
    error_log("Error in medicines.php: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "message" => "Internal server error: " . $e->getMessage()
    ], 500);
}
?>