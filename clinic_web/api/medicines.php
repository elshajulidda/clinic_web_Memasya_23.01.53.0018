
<?php
include_once __DIR__ . '/../config/database.php';
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
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . implode(", ", $stmt->errorInfo()));
            }
            
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
            
            // Debug log
            error_log("Medicine POST data: " . json_encode($data));
            
            // Validasi input
            $required = ['code', 'name', 'unit', 'price'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Check for duplicate code
            $checkQuery = "SELECT id FROM medicines WHERE code = :code AND is_active = 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':code', $data['code']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Medicine code already exists"
                ], 400);
            }
            
            $query = "INSERT INTO medicines 
                     (code, name, generic_name, category, unit, price, stock, min_stock, supplier, expiry_date, description) 
                     VALUES 
                     (:code, :name, :generic_name, :category, :unit, :price, :stock, :min_stock, :supplier, :expiry_date, :description)";
            
            $stmt = $db->prepare($query);
            
            // Bind parameters dengan tipe data yang tepat - PERBAIKAN DI SINI
            $code = trim($data['code']);
            $name = trim($data['name']);
            $generic_name = isset($data['generic_name']) ? trim($data['generic_name']) : '';
            $category = isset($data['category']) ? trim($data['category']) : '';
            $unit = trim($data['unit']);
            $price = (float)$data['price'];
            $stock = isset($data['stock']) ? (int)$data['stock'] : 0;
            $min_stock = isset($data['min_stock']) ? (int)$data['min_stock'] : 0;
            $supplier = isset($data['supplier']) ? trim($data['supplier']) : '';
            $expiry_date = !empty($data['expiry_date']) ? $data['expiry_date'] : null;
            $description = isset($data['description']) ? trim($data['description']) : '';
            
            // Bind parameter dengan value langsung, bukan reference ke expression
            $stmt->bindValue(':code', $code);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':generic_name', $generic_name);
            $stmt->bindValue(':category', $category);
            $stmt->bindValue(':unit', $unit);
            $stmt->bindValue(':price', $price);
            $stmt->bindValue(':stock', $stock);
            $stmt->bindValue(':min_stock', $min_stock);
            $stmt->bindValue(':supplier', $supplier);
            $stmt->bindValue(':expiry_date', $expiry_date);
            $stmt->bindValue(':description', $description);
            
            if ($stmt->execute()) {
                $medicine_id = $db->lastInsertId();
                sendJsonResponse([
                    "success" => true,
                    "message" => "Medicine created successfully",
                    "medicine_id" => $medicine_id
                ], 201);
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Failed to create medicine: " . $errorInfo[2]);
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
            $checkQuery = "SELECT id FROM medicines WHERE id = :id AND is_active = 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Medicine not found"
                ], 404);
            }
            
            $updateFields = [];
            $params = [':id' => $id];
            
            $allowedFields = ['code', 'name', 'generic_name', 'category', 'unit', 'price', 'stock', 'min_stock', 'supplier', 'expiry_date', 'description'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    // Handle different data types
                    if (in_array($field, ['price'])) {
                        $params[":$field"] = (float)$data[$field];
                    } elseif (in_array($field, ['stock', 'min_stock'])) {
                        $params[":$field"] = (int)$data[$field];
                    } else {
                        $params[":$field"] = trim($data[$field]);
                    }
                }
            }
            
            if (empty($updateFields)) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "No fields to update"
                ], 400);
            }
            
            // Check for duplicate code if code is being updated
            if (isset($data['code'])) {
                $checkCodeQuery = "SELECT id FROM medicines WHERE code = :code AND id != :id AND is_active = 1";
                $checkCodeStmt = $db->prepare($checkCodeQuery);
                $checkCodeStmt->bindValue(':code', trim($data['code']));
                $checkCodeStmt->bindValue(':id', $id);
                $checkCodeStmt->execute();
                
                if ($checkCodeStmt->rowCount() > 0) {
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Medicine code already exists"
                    ], 400);
                }
            }
            
            $updateQuery = "UPDATE medicines SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            
            // Gunakan bindValue untuk semua parameter
            foreach ($params as $key => $value) {
                $updateStmt->bindValue($key, $value);
            }
            
            if ($updateStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Medicine updated successfully"
                ]);
            } else {
                $errorInfo = $updateStmt->errorInfo();
                throw new Exception("Failed to update medicine: " . $errorInfo[2]);
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
            $stmt->bindValue(':id', $id);
            
            if ($stmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Medicine deleted successfully"
                ]);
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Failed to delete medicine: " . $errorInfo[2]);
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
        "message" => "Error: " . $e->getMessage()
    ], 500);
}
?>
