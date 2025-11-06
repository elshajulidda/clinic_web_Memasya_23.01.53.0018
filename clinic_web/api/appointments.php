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
            $query = "SELECT 
                a.*,
                p.medical_record_number,
                p.full_name as patient_name,
                p.phone as patient_phone,
                u.full_name as doctor_name,
                d.specialization,
                r.name as room_name,
                c.name as clinic_name,
                uc.full_name as created_by_name
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN rooms r ON a.room_id = r.id
            LEFT JOIN clinics c ON r.clinic_id = c.id
            LEFT JOIN users uc ON a.created_by = uc.id
            ORDER BY a.scheduled_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $appointments = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $appointments[] = [
                    "id" => (int)$row['id'],
                    "patient_id" => $row['patient_id'] ? (int)$row['patient_id'] : null,
                    "doctor_id" => $row['doctor_id'] ? (int)$row['doctor_id'] : null,
                    "room_id" => $row['room_id'] ? (int)$row['room_id'] : null,
                    "medical_record_number" => $row['medical_record_number'],
                    "patient_name" => $row['patient_name'],
                    "patient_phone" => $row['patient_phone'],
                    "doctor_name" => $row['doctor_name'],
                    "specialization" => $row['specialization'],
                    "room_name" => $row['room_name'],
                    "clinic_name" => $row['clinic_name'],
                    "scheduled_at" => $row['scheduled_at'],
                    "status" => $row['status'],
                    "type" => $row['type'],
                    "complaint" => $row['complaint'],
                    "notes" => $row['notes'],
                    "created_by_name" => $row['created_by_name'],
                    "created_at" => $row['created_at']
                ];
            }
            
            sendJsonResponse($appointments);
            break;
            
        case 'POST':
            $data = getRequestData();
            
            // Validate required fields
            $required = ['patient_id', 'doctor_id', 'room_id', 'scheduled_at'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Field '$field' is required"
                    ], 400);
                }
            }
            
            // Check for scheduling conflicts
            $conflictQuery = "SELECT COUNT(*) as conflict_count 
                            FROM appointments 
                            WHERE doctor_id = :doctor_id 
                            AND scheduled_at = :scheduled_at 
                            AND status != 'cancelled'";
            $conflictStmt = $db->prepare($conflictQuery);
            $conflictStmt->bindParam(':doctor_id', $data['doctor_id']);
            $conflictStmt->bindParam(':scheduled_at', $data['scheduled_at']);
            $conflictStmt->execute();
            $conflict = $conflictStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($conflict['conflict_count'] > 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor already has an appointment at this time"
                ], 400);
            }
            
            $query = "INSERT INTO appointments 
                     (patient_id, doctor_id, room_id, scheduled_at, status, type, complaint, notes, created_by) 
                     VALUES 
                     (:patient_id, :doctor_id, :room_id, :scheduled_at, :status, :type, :complaint, :notes, :created_by)";
            
            $stmt = $db->prepare($query);
            
            // Bind parameters dengan nilai default
            $patient_id = !empty($data['patient_id']) ? $data['patient_id'] : null;
            $doctor_id = !empty($data['doctor_id']) ? $data['doctor_id'] : null;
            $room_id = !empty($data['room_id']) ? $data['room_id'] : null;
            $scheduled_at = !empty($data['scheduled_at']) ? $data['scheduled_at'] : null;
            $status = !empty($data['status']) ? $data['status'] : 'scheduled';
            $type = !empty($data['type']) ? $data['type'] : 'consultation';
            $complaint = !empty($data['complaint']) ? $data['complaint'] : '';
            $notes = !empty($data['notes']) ? $data['notes'] : '';
            $created_by = !empty($data['created_by']) ? $data['created_by'] : 1;
            
            $stmt->bindParam(':patient_id', $patient_id);
            $stmt->bindParam(':doctor_id', $doctor_id);
            $stmt->bindParam(':room_id', $room_id);
            $stmt->bindParam(':scheduled_at', $scheduled_at);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':complaint', $complaint);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':created_by', $created_by);
            
            if ($stmt->execute()) {
                $lastId = $db->lastInsertId();
                
                sendJsonResponse([
                    "success" => true,
                    "message" => "Appointment created successfully",
                    "appointment_id" => $lastId
                ], 201);
            } else {
                $errorInfo = $stmt->errorInfo();
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to create appointment: " . $errorInfo[2]
                ], 500);
            }
            break;
            
        case 'PUT':
            // Get ID from query string or from JSON data
            $input = getRequestData();
            $id = $_GET['id'] ?? $input['id'] ?? null;
            
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Appointment ID is required"
                ], 400);
            }
            
            $data = $input;
            
            // Check if appointment exists
            $checkQuery = "SELECT id FROM appointments WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Appointment not found"
                ], 404);
            }
            
            // Build update query dynamically based on provided fields
            $updateFields = [];
            $params = [':id' => $id];
            
            $allowedFields = ['patient_id', 'doctor_id', 'room_id', 'scheduled_at', 'status', 'type', 'complaint', 'notes'];
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
            
            $updateQuery = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            
            foreach ($params as $key => $value) {
                $updateStmt->bindValue($key, $value);
            }
            
            if ($updateStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Appointment updated successfully"
                ]);
            } else {
                $errorInfo = $updateStmt->errorInfo();
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to update appointment: " . $errorInfo[2]
                ], 500);
            }
            break;
            
        case 'DELETE':
            // Get ID from query string or from JSON data
            $input = getRequestData();
            $id = $_GET['id'] ?? $input['id'] ?? null;
            
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Appointment ID is required"
                ], 400);
            }
            
            // Check if appointment exists
            $checkQuery = "SELECT id, status FROM appointments WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Appointment not found"
                ], 404);
            }
            
            $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Don't allow deletion of completed appointments
            if ($appointment['status'] === 'completed') {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Cannot delete completed appointments"
                ], 400);
            }
            
            $deleteQuery = "DELETE FROM appointments WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $id);
            
            if ($deleteStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Appointment deleted successfully"
                ]);
            } else {
                $errorInfo = $deleteStmt->errorInfo();
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to delete appointment: " . $errorInfo[2]
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
    error_log("Error in appointments.php: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "message" => "Internal server error: " . $e->getMessage()
    ], 500);
}
?>