<?php
include_once __DIR__ . '/../config/database.php';
handleCors();

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $input = getRequestData();

    error_log("Patients API - Method: $method, Data: " . json_encode($input));

    switch($method) {
        case 'GET':
            $query = "SELECT * FROM patients ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Query execution failed: " . $errorInfo[2]);
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
            
            sendJsonResponse($patients);
            break;
            
        case 'POST':
            // Validasi input
            if (empty($input['full_name'])) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Nama lengkap harus diisi"
                ], 400);
            }

            // Generate medical record number jika tidak ada
            if (empty($input['medical_record_number'])) {
                $countQuery = "SELECT COUNT(*) as count FROM patients";
                $countStmt = $db->prepare($countQuery);
                $countStmt->execute();
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
                $input['medical_record_number'] = 'RM' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            $query = "INSERT INTO patients 
                     (medical_record_number, full_name, gender, birth_date, phone, email, address, blood_type, allergies, emergency_contact) 
                     VALUES 
                     (:medical_record_number, :full_name, :gender, :birth_date, :phone, :email, :address, :blood_type, :allergies, :emergency_contact)";
            
            $stmt = $db->prepare($query);
            
            // Bind parameters dengan nilai default yang aman
            $medical_record_number = $input['medical_record_number'] ?? '';
            $full_name = $input['full_name'] ?? '';
            $gender = $input['gender'] ?? 'M';
            $birth_date = !empty($input['birth_date']) ? $input['birth_date'] : null;
            $phone = $input['phone'] ?? '';
            $email = $input['email'] ?? '';
            $address = $input['address'] ?? '';
            $blood_type = $input['blood_type'] ?? '';
            $allergies = $input['allergies'] ?? '';
            $emergency_contact = $input['emergency_contact'] ?? '';
            
            $stmt->bindParam(':medical_record_number', $medical_record_number);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':birth_date', $birth_date);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':blood_type', $blood_type);
            $stmt->bindParam(':allergies', $allergies);
            $stmt->bindParam(':emergency_contact', $emergency_contact);
            
            if ($stmt->execute()) {
                $lastId = $db->lastInsertId();
                sendJsonResponse([
                    "success" => true,
                    "message" => "Pasien berhasil ditambahkan",
                    "patient_id" => $lastId,
                    "medical_record_number" => $medical_record_number
                ], 201);
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Gagal menambahkan pasien: " . $errorInfo[2]);
            }
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? $input['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "ID pasien diperlukan"
                ], 400);
            }
            
            // Check if patient exists
            $checkQuery = "SELECT id FROM patients WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Pasien tidak ditemukan"
                ], 404);
            }
            
            // Build update query
            $updateFields = [];
            $params = [':id' => $id];
            
            $allowedFields = [
                'medical_record_number', 'full_name', 'gender', 'birth_date', 
                'phone', 'email', 'address', 'blood_type', 'allergies', 'emergency_contact'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Tidak ada data yang diupdate"
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
                    "message" => "Data pasien berhasil diupdate"
                ]);
            } else {
                $errorInfo = $updateStmt->errorInfo();
                throw new Exception("Gagal mengupdate pasien: " . $errorInfo[2]);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? $input['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "ID pasien diperlukan"
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
                    "message" => "Tidak dapat menghapus pasien yang memiliki janji temu"
                ], 400);
            }
            
            $deleteQuery = "DELETE FROM patients WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $id);
            
            if ($deleteStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Pasien berhasil dihapus"
                ]);
            } else {
                $errorInfo = $deleteStmt->errorInfo();
                throw new Exception("Gagal menghapus pasien: " . $errorInfo[2]);
            }
            break;
            
        default:
            sendJsonResponse([
                "success" => false,
                "message" => "Method tidak diizinkan"
            ], 405);
    }
    
} catch (Exception $e) {
    error_log("Error in patients.php: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ], 500);
}
?>  