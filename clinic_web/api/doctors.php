
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
            $query = "SELECT d.*, u.full_name, u.email, u.phone 
                     FROM doctors d 
                     LEFT JOIN users u ON d.user_id = u.id 
                     WHERE u.full_name IS NOT NULL 
                     ORDER BY u.full_name";
            
            $stmt = $db->prepare($query);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Failed to execute query: " . $errorInfo[2]);
            }
            
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
            
            // Debug log
            error_log("Doctor POST data: " . json_encode($data));
            
            // Validasi input
            if (empty($data['full_name']) || empty($data['specialization'])) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Full name and specialization are required"
                ], 400);
            }
            
            // Generate username yang unik
            $baseUsername = strtolower(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $data['full_name'])));
            $username = $baseUsername;
            $counter = 1;
            
            // Cek apakah username sudah ada
            do {
                $checkQuery = "SELECT id FROM users WHERE username = :username";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindValue(':username', $username);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() > 0) {
                    $username = $baseUsername . '_' . $counter;
                    $counter++;
                } else {
                    break;
                }
            } while ($counter < 100);
            
            $password = password_hash('doctor123', PASSWORD_DEFAULT);
            
            // Mulai transaction
            $db->beginTransaction();
            
            try {
                // Insert user - PERBAIKAN BINDING PARAMETER DI SINI
                $userQuery = "INSERT INTO users (username, password, full_name, email, phone, role_id) 
                             VALUES (:username, :password, :full_name, :email, :phone, 2)";
                
                $userStmt = $db->prepare($userQuery);
                
                // Gunakan bindValue bukan bindParam
                $userStmt->bindValue(':username', $username);
                $userStmt->bindValue(':password', $password);
                $userStmt->bindValue(':full_name', $data['full_name']);
                $userStmt->bindValue(':email', $data['email'] ?? '');
                $userStmt->bindValue(':phone', $data['phone'] ?? '');
                
                if (!$userStmt->execute()) {
                    $errorInfo = $userStmt->errorInfo();
                    throw new Exception("Failed to create user account: " . $errorInfo[2]);
                }
                
                $user_id = $db->lastInsertId();
                
                // Insert doctor - PERBAIKAN BINDING PARAMETER DI SINI
                $doctorQuery = "INSERT INTO doctors 
                               (user_id, specialization, license_number, experience_years, education, schedule) 
                               VALUES 
                               (:user_id, :specialization, :license_number, :experience_years, :education, :schedule)";
                
                $doctorStmt = $db->prepare($doctorQuery);
                
                // Gunakan bindValue dan handle null values
                $doctorStmt->bindValue(':user_id', $user_id);
                $doctorStmt->bindValue(':specialization', $data['specialization']);
                $doctorStmt->bindValue(':license_number', $data['license_number'] ?? '');
                
                // Handle experience_years - pastikan integer atau null
                $experience_years = isset($data['experience_years']) && $data['experience_years'] !== '' 
                    ? (int)$data['experience_years'] 
                    : null;
                $doctorStmt->bindValue(':experience_years', $experience_years, $experience_years !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                
                $doctorStmt->bindValue(':education', $data['education'] ?? '');
                $doctorStmt->bindValue(':schedule', $data['schedule'] ?? '');
                
                if (!$doctorStmt->execute()) {
                    $errorInfo = $doctorStmt->errorInfo();
                    throw new Exception("Failed to create doctor record: " . $errorInfo[2]);
                }
                
                $doctor_id = $db->lastInsertId();
                
                $db->commit();
                
                sendJsonResponse([
                    "success" => true,
                    "message" => "Doctor created successfully",
                    "doctor_id" => $doctor_id,
                    "user_id" => $user_id
                ], 201);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception("Failed to create doctor: " . $e->getMessage());
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
            $checkStmt->bindValue(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor not found"
                ], 404);
            }
            
            $doctor = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $doctor['user_id'];
            
            $db->beginTransaction();
            
            try {
                // Update user data jika ada - PERBAIKAN BINDING DI SINI
                $userUpdateFields = [];
                $userParams = [':user_id' => $user_id];
                
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
                    
                    foreach ($userParams as $key => $value) {
                        $userUpdateStmt->bindValue($key, $value);
                    }
                    
                    if (!$userUpdateStmt->execute()) {
                        $errorInfo = $userUpdateStmt->errorInfo();
                        throw new Exception("Failed to update user data: " . $errorInfo[2]);
                    }
                }
                
                // Update doctor data - PERBAIKAN BINDING DI SINI
                $doctorUpdateFields = [];
                $doctorParams = [':id' => $id];
                
                $fields = ['specialization', 'license_number', 'experience_years', 'education', 'schedule', 'is_available'];
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $doctorUpdateFields[] = "$field = :$field";
                        if ($field === 'experience_years') {
                            $doctorParams[":$field"] = (int)$data[$field];
                        } elseif ($field === 'is_available') {
                            $doctorParams[":$field"] = (bool)$data[$field];
                        } else {
                            $doctorParams[":$field"] = $data[$field];
                        }
                    }
                }
                
                if (!empty($doctorUpdateFields)) {
                    $doctorUpdateQuery = "UPDATE doctors SET " . implode(', ', $doctorUpdateFields) . " WHERE id = :id";
                    $doctorUpdateStmt = $db->prepare($doctorUpdateQuery);
                    
                    foreach ($doctorParams as $key => $value) {
                        $doctorUpdateStmt->bindValue($key, $value);
                    }
                    
                    if (!$doctorUpdateStmt->execute()) {
                        $errorInfo = $doctorUpdateStmt->errorInfo();
                        throw new Exception("Failed to update doctor data: " . $errorInfo[2]);
                    }
                }
                
                $db->commit();
                
                sendJsonResponse([
                    "success" => true,
                    "message" => "Doctor updated successfully"
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception("Failed to update doctor: " . $e->getMessage());
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
            
            // Check for active appointments
            $checkAppointments = "SELECT COUNT(*) as appointment_count FROM appointments WHERE doctor_id = :id AND status != 'cancelled'";
            $checkStmt = $db->prepare($checkAppointments);
            $checkStmt->bindValue(':id', $id);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['appointment_count'] > 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Cannot delete doctor with active appointments"
                ], 400);
            }
            
            // Get user_id
            $getUserQuery = "SELECT user_id FROM doctors WHERE id = :id";
            $getUserStmt = $db->prepare($getUserQuery);
            $getUserStmt->bindValue(':id', $id);
            $getUserStmt->execute();
            
            if ($getUserStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Doctor not found"
                ], 404);
            }
            
            $doctor = $getUserStmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $doctor['user_id'];
            
            $db->beginTransaction();
            
            try {
                // Delete doctor record
                $deleteDoctorQuery = "DELETE FROM doctors WHERE id = :id";
                $deleteDoctorStmt = $db->prepare($deleteDoctorQuery);
                $deleteDoctorStmt->bindValue(':id', $id);
                
                if (!$deleteDoctorStmt->execute()) {
                    $errorInfo = $deleteDoctorStmt->errorInfo();
                    throw new Exception("Failed to delete doctor record: " . $errorInfo[2]);
                }
                
                // Delete user account
                $deleteUserQuery = "DELETE FROM users WHERE id = :user_id";
                $deleteUserStmt = $db->prepare($deleteUserQuery);
                $deleteUserStmt->bindValue(':user_id', $user_id);
                
                if (!$deleteUserStmt->execute()) {
                    $errorInfo = $deleteUserStmt->errorInfo();
                    throw new Exception("Failed to delete user account: " . $errorInfo[2]);
                }
                
                $db->commit();
                
                sendJsonResponse([
                    "success" => true,
                    "message" => "Doctor deleted successfully"
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception("Failed to delete doctor: " . $e->getMessage());
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
        "message" => "Error: " . $e->getMessage()
    ], 500);
}
?>