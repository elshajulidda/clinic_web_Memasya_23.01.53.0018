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
            // Get all doctors with user information
            $query = "SELECT d.*, u.full_name, u.email, u.phone 
                     FROM doctors d 
                     LEFT JOIN users u ON d.user_id = u.id 
                     WHERE u.full_name IS NOT NULL 
                     ORDER BY u.full_name";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $doctors = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $doctors[] = [
                    "id" => (int)$row['id'],
                    "user_id" => $row['user_id'] ? (int)$row['user_id'] : null,
                    "full_name" => $row['full_name'],
                    "email" => $row['email'],
                    "phone" => $row['phone'],
                    "specialization" => $row['specialization'],
                    "license_number" => $row['license_number'],
                    "experience_years" => $row['experience_years'] ? (int)$row['experience_years'] : null,
                    "education" => $row['education'],
                    "schedule" => $row['schedule'],
                    "is_available" => (bool)$row['is_available'],
                    "created_at" => $row['created_at']
                ];
            }
            
            sendJsonResponse($doctors);
            break;
            
        case 'POST':
            $data = getRequestData();
            
            // Validate required fields
            if (empty($data['full_name']) || empty($data['specialization'])) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Full name and specialization are required"
                ], 400);
            }
            
            // First create user
            $userQuery = "INSERT INTO users (username, password, full_name, email, phone, role_id) 
                         VALUES (:username, :password, :full_name, :email, :phone, 2)";
            
            $username = strtolower(str_replace(' ', '_', $data['full_name']));
            $password = password_hash('doctor123', PASSWORD_DEFAULT);
            
            $userStmt = $db->prepare($userQuery);
            $userStmt->bindParam(':username', $username);
            $userStmt->bindParam(':password', $password);
            $userStmt->bindParam(':full_name', $data['full_name']);
            $userStmt->bindParam(':email', $data['email'] ?? '');
            $userStmt->bindParam(':phone', $data['phone'] ?? '');
            
            if ($userStmt->execute()) {
                $user_id = $db->lastInsertId();
                
                // Then create doctor record
                $doctorQuery = "INSERT INTO doctors 
                               (user_id, specialization, license_number, experience_years, education, schedule) 
                               VALUES 
                               (:user_id, :specialization, :license_number, :experience_years, :education, :schedule)";
                
                $doctorStmt = $db->prepare($doctorQuery);
                $doctorStmt->bindParam(':user_id', $user_id);
                $doctorStmt->bindParam(':specialization', $data['specialization']);
                $doctorStmt->bindParam(':license_number', $data['license_number'] ?? '');
                $doctorStmt->bindParam(':experience_years', $data['experience_years'] ?? null);
                $doctorStmt->bindParam(':education', $data['education'] ?? '');
                $doctorStmt->bindParam(':schedule', $data['schedule'] ?? '');
                
                if ($doctorStmt->execute()) {
                    sendJsonResponse([
                        "success" => true,
                        "message" => "Doctor created successfully",
                        "doctor_id" => $db->lastInsertId()
                    ], 201);
                } else {
                    // Rollback user creation if doctor creation fails
                    $db->query("DELETE FROM users WHERE id = $user_id");
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Failed to create doctor record"
                    ], 500);
                }
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to create user account"
                ], 500);
            }
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor ID is required"
                ], 400);
            }
            
            $data = getRequestData();
            
            // Check if doctor exists
            $checkQuery = "SELECT d.id, d.user_id FROM doctors d WHERE d.id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor not found"
                ], 404);
            }
            
            $doctor = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $doctor['user_id'];
            
            // Update user information
            if (isset($data['full_name']) || isset($data['email']) || isset($data['phone'])) {
                $userUpdateFields = [];
                $userParams = [];
                
                if (isset($data['full_name'])) {
                    $userUpdateFields[] = "full_name = :full_name";
                    $userParams[':full_name'] = $data['full_name'];
                }
                if (isset($data['email'])) {
                    $userUpdateFields[] = "email = :email";
                    $userParams[':email'] = $data['email'];
                }
                if (isset($data['phone'])) {
                    $userUpdateFields[] = "phone = :phone";
                    $userParams[':phone'] = $data['phone'];
                }
                
                if (!empty($userUpdateFields)) {
                    $userUpdateQuery = "UPDATE users SET " . implode(', ', $userUpdateFields) . " WHERE id = :user_id";
                    $userUpdateStmt = $db->prepare($userUpdateQuery);
                    $userUpdateStmt->bindParam(':user_id', $user_id);
                    
                    foreach ($userParams as $key => $value) {
                        $userUpdateStmt->bindValue($key, $value);
                    }
                    
                    $userUpdateStmt->execute();
                }
            }
            
            // Update doctor information
            $doctorUpdateFields = [];
            $doctorParams = [':id' => $id];
            
            $fields = ['specialization', 'license_number', 'experience_years', 'education', 'schedule', 'is_available'];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $doctorUpdateFields[] = "$field = :$field";
                    $doctorParams[":$field"] = $data[$field];
                }
            }
            
            if (!empty($doctorUpdateFields)) {
                $doctorUpdateQuery = "UPDATE doctors SET " . implode(', ', $doctorUpdateFields) . " WHERE id = :id";
                $doctorUpdateStmt = $db->prepare($doctorUpdateQuery);
                
                foreach ($doctorParams as $key => $value) {
                    $doctorUpdateStmt->bindValue($key, $value);
                }
                
                if ($doctorUpdateStmt->execute()) {
                    sendJsonResponse([
                        "success" => true,
                        "message" => "Doctor updated successfully"
                    ]);
                } else {
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Failed to update doctor"
                    ], 500);
                }
            } else {
                sendJsonResponse([
                    "success" => true,
                    "message" => "No fields to update"
                ]);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor ID is required"
                ], 400);
            }
            
            // Check if doctor has appointments
            $checkAppointments = "SELECT COUNT(*) as appointment_count FROM appointments WHERE doctor_id = :id AND status != 'cancelled'";
            $checkStmt = $db->prepare($checkAppointments);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['appointment_count'] > 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Cannot delete doctor with active appointments"
                ], 400);
            }
            
            // Get user_id first
            $getUserQuery = "SELECT user_id FROM doctors WHERE id = :id";
            $getUserStmt = $db->prepare($getUserQuery);
            $getUserStmt->bindParam(':id', $id);
            $getUserStmt->execute();
            
            if ($getUserStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor not found"
                ], 404);
            }
            
            $doctor = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $doctor['user_id'];
            
            // Delete doctor record
            $deleteDoctorQuery = "DELETE FROM doctors WHERE id = :id";
            $deleteDoctorStmt = $db->prepare($deleteDoctorQuery);
            $deleteDoctorStmt->bindParam(':id', $id);
            
            if ($deleteDoctorStmt->execute()) {
                // Also delete user account
                $deleteUserQuery = "DELETE FROM users WHERE id = :user_id";
                $deleteUserStmt = $db->prepare($deleteUserQuery);
                $deleteUserStmt->bindParam(':user_id', $user_id);
                $deleteUserStmt->execute();
                
                sendJsonResponse([
                    "success" => true,
                    "message" => "Doctor deleted successfully"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Failed to delete doctor"
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
    error_log("Error in doctors.php: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "message" => "Internal server error: " . $e->getMessage()
    ], 500);
}
?>