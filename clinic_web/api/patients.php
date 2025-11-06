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
            error_log("Patients API called"); // Debug log
            
            $query = "SELECT * FROM patients ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            
            if (!$stmt->execute()) {
                error_log("Query failed: " . implode(", ", $stmt->errorInfo()));
                sendJsonResponse([
                    "success" => false,
                    "message" => "Query execution failed"
                ], 500);
            }
            
            $patients = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $patients[] = [
                    "id" => (int)$row['id'],
                    "medical_record_number" => $row['medical_record_number'],
                    "full_name" => $row['full_name'],
                    "gender" => $row['gender'],
                    "birth_date" => $row['birth_date'],
                    "phone" => $row['phone'],
                    "email" => $row['email'],
                    "address" => $row['address'],
                    "blood_type" => $row['blood_type'],
                    "allergies" => $row['allergies'],
                    "emergency_contact" => $row['emergency_contact'],
                    "created_at" => $row['created_at']
                ];
            }
            
            error_log("Found " . count($patients) . " patients"); // Debug log
            sendJsonResponse($patients);
            break;
            
        case 'POST':
            $data = getRequestData();
            
            // Validate required fields
            if (empty($data['full_name'])) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Full name is required"
                ], 400);
            }
            
            // Generate medical record number if not provided
            if (empty($data['medical_record_number'])) {
                $countQuery = "SELECT COUNT(*) as count FROM patients";
                $countStmt = $db->prepare($countQuery);
                $countStmt->execute();
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
                $data['medical_record_number'] = 'RM' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            
            $query = "INSERT INTO patients 
                     (medical_record_number, full_name, gender, birth_date, phone, email, address, blood_type, allergies, emergency_contact) 
                     VALUES 
                     (:medical_record_number, :full_name, :gender, :birth_date, :phone, :email, :address, :blood_type, :allergies, :emergency_contact)";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':medical_record_number', $data['medical_record_number']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':gender', $data['gender'] ?? 'M');
            $stmt->bindParam(':birth_date', $data['birth_date'] ?? null);
            $stmt->bindParam(':phone', $data['phone'] ?? '');
            $stmt->bindParam(':email', $data['email'] ?? '');
            $stmt->bindParam(':address', $data['address'] ?? '');
            $stmt->bindParam(':blood_type', $data['blood_type'] ?? null);
            $stmt->bindParam(':allergies', $data['allergies'] ?? '');
            $stmt->bindParam(':emergency_contact', $data['emergency_contact'] ?? '');
            
            if ($stmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Patient created successfully",
                    "patient_id" => $db->lastInsertId(),
                    "medical_record_number" => $data['medical_record_number']
                ], 201);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to create patient"
                ], 500);
            }
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Patient ID is required"
                ], 400);
            }
            
            $data = getRequestData();
            
            // Check if patient exists
            $checkQuery = "SELECT id FROM patients WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Patient not found"
                ], 404);
            }
            
            // Build update query
            $updateFields = [];
            $params = [':id' => $id];
            
            $allowedFields = ['medical_record_number', 'full_name', 'gender', 'birth_date', 'phone', 'email', 'address', 'blood_type', 'allergies', 'emergency_contact'];
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
            
            $updateQuery = "UPDATE patients SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            
            foreach ($params as $key => $value) {
                $updateStmt->bindValue($key, $value);
            }
            
            if ($updateStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Patient updated successfully"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to update patient"
                ], 500);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Patient ID is required"
                ], 400);
            }
            
            // Check if patient has appointments
            $checkAppointments = "SELECT COUNT(*) as appointment_count FROM appointments WHERE patient_id = :id";
            $checkStmt = $db->prepare($checkAppointments);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['appointment_count'] > 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Cannot delete patient with existing appointments"
                ], 400);
            }
            
            $deleteQuery = "DELETE FROM patients WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $id);
            
            if ($deleteStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Patient deleted successfully"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to delete patient"
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
    error_log("Error in patients.php: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "message" => "Internal server error: " . $e->getMessage()
    ], 500);
}
?>