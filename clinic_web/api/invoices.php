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
            // Query yang diperbaiki - sesuaikan dengan struktur tabel yang ada
            $query = "SELECT i.*, 
                     a.scheduled_at, 
                     p.full_name as patient_name,
                     p.medical_record_number,
                     u.full_name as doctor_name
                     FROM invoices i
                     LEFT JOIN appointments a ON i.appointment_id = a.id
                     LEFT JOIN patients p ON a.patient_id = p.id
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     LEFT JOIN users u ON d.user_id = u.id
                     ORDER BY i.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $invoices = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $invoices[] = [
                    "id" => (int)$row['id'],
                    "appointment_id" => $row['appointment_id'] ? (int)$row['appointment_id'] : null,
                    "patient_id" => $row['patient_id'] ? (int)$row['patient_id'] : null,
                    "invoice_number" => $row['invoice_number'],
                    "total_amount" => (float)$row['total_amount'],
                    "paid_amount" => (float)$row['paid_amount'],
                    "payment_method" => $row['payment_method'],
                    "status" => $row['payment_status'], // Sesuaikan dengan nama kolom di database
                    "due_date" => $row['due_date'],
                    "patient_name" => $row['patient_name'],
                    "doctor_name" => $row['doctor_name'],
                    "scheduled_at" => $row['scheduled_at'],
                    "created_at" => $row['created_at']
                ];
            }
            
            sendJsonResponse($invoices);
            break;
            
        case 'POST':
            $data = getRequestData();
            
            // Validasi field yang diperlukan
            if (empty($data['appointment_id']) || empty($data['total_amount'])) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Appointment ID dan total amount diperlukan"
                ], 400);
            }
            
            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO invoices 
                     (appointment_id, patient_id, invoice_number, total_amount, paid_amount, payment_method, payment_status, due_date, created_by) 
                     VALUES 
                     (:appointment_id, :patient_id, :invoice_number, :total_amount, :paid_amount, :payment_method, :payment_status, :due_date, :created_by)";
            
            $stmt = $db->prepare($query);
            
            // Dapatkan patient_id dari appointment
            $appointmentQuery = "SELECT patient_id FROM appointments WHERE id = :appointment_id";
            $appointmentStmt = $db->prepare($appointmentQuery);
            $appointmentStmt->bindParam(':appointment_id', $data['appointment_id']);
            $appointmentStmt->execute();
            $appointment = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Appointment tidak ditemukan"
                ], 404);
            }
            
            $patient_id = $appointment['patient_id'];
            $due_date = date('Y-m-d', strtotime('+7 days'));
            $paid_amount = $data['paid_amount'] ?? 0.00;
            $payment_method = $data['payment_method'] ?? 'cash';
            $payment_status = $paid_amount >= $data['total_amount'] ? 'paid' : 'unpaid';
            
            $stmt->bindParam(':appointment_id', $data['appointment_id']);
            $stmt->bindParam(':patient_id', $patient_id);
            $stmt->bindParam(':invoice_number', $invoiceNumber);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':paid_amount', $paid_amount);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->bindParam(':payment_status', $payment_status);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':created_by', $data['created_by'] ?? 1);
            
            if($stmt->execute()) {
                sendJsonResponse([
                    "success" => true, 
                    "message" => "Invoice berhasil dibuat", 
                    "invoice_number" => $invoiceNumber,
                    "invoice_id" => $db->lastInsertId()
                ], 201);
            } else {
                sendJsonResponse([
                    "success" => false, 
                    "message" => "Gagal membuat invoice"
                ], 500);
            }
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Invoice ID diperlukan"
                ], 400);
            }
            
            $data = getRequestData();
            
            // Check if invoice exists
            $checkQuery = "SELECT id FROM invoices WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Invoice tidak ditemukan"
                ], 404);
            }
            
            if (isset($data['paid_amount'])) {
                // Update payment - hitung status berdasarkan pembayaran
                $getTotalQuery = "SELECT total_amount, paid_amount FROM invoices WHERE id = :id";
                $getTotalStmt = $db->prepare($getTotalQuery);
                $getTotalStmt->bindParam(':id', $id);
                $getTotalStmt->execute();
                $invoiceData = $getTotalStmt->fetch(PDO::FETCH_ASSOC);
                
                $newPaidAmount = $data['paid_amount'];
                $totalAmount = $invoiceData['total_amount'];
                $status = 'unpaid';
                
                if ($newPaidAmount >= $totalAmount) {
                    $status = 'paid';
                } elseif ($newPaidAmount > 0) {
                    $status = 'partial';
                }
                
                $updateFields = [
                    "paid_amount = :paid_amount",
                    "payment_status = :status",
                    "paid_at = CASE WHEN :status = 'paid' THEN NOW() ELSE NULL END"
                ];
                
                $params = [
                    ':id' => $id,
                    ':paid_amount' => $newPaidAmount,
                    ':status' => $status
                ];
                
                // Jika ada payment_method, update juga
                if (isset($data['payment_method'])) {
                    $updateFields[] = "payment_method = :payment_method";
                    $params[':payment_method'] = $data['payment_method'];
                }
                
                $query = "UPDATE invoices SET " . implode(', ', $updateFields) . " WHERE id = :id";
            } else {
                // Update invoice details
                $updateFields = [];
                $params = [':id' => $id];
                
                if (isset($data['total_amount'])) {
                    $updateFields[] = "total_amount = :total_amount";
                    $params[':total_amount'] = $data['total_amount'];
                }
                
                if (isset($data['payment_method'])) {
                    $updateFields[] = "payment_method = :payment_method";
                    $params[':payment_method'] = $data['payment_method'];
                }
                
                if (empty($updateFields)) {
                    sendJsonResponse([
                        "success" => false,
                        "message" => "Tidak ada data yang diupdate"
                    ], 400);
                }
                
                $query = "UPDATE invoices SET " . implode(', ', $updateFields) . " WHERE id = :id";
            }
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if($stmt->execute()) {
                sendJsonResponse([
                    "success" => true, 
                    "message" => "Invoice berhasil diupdate"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false, 
                    "message" => "Gagal mengupdate invoice"
                ], 500);
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Invoice ID diperlukan"
                ], 400);
            }
            
            // Check if invoice exists and can be deleted
            $checkQuery = "SELECT payment_status FROM invoices WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Invoice tidak ditemukan"
                ], 404);
            }
            
            $invoice = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Only allow deletion of unpaid or partially paid invoices
            if ($invoice['payment_status'] === 'paid') {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Tidak dapat menghapus invoice yang sudah lunas"
                ], 400);
            }
            
            $deleteQuery = "DELETE FROM invoices WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $id);
            
            if ($deleteStmt->execute()) {
                sendJsonResponse([
                    "success" => true,
                    "message" => "Invoice berhasil dihapus"
                ]);
            } else {
                sendJsonResponse([
                    "success" => false,
                    "message" => "Gagal menghapus invoice"
                ], 500);
            }
            break;
            
        default:
            sendJsonResponse([
                "success" => false,
                "message" => "Method tidak diizinkan"
            ], 405);
    }
    
} catch (Exception $e) {
    error_log("Error in invoices.php: " . $e->getMessage());
    sendJsonResponse([
        "success" => false,
        "message" => "Internal server error: " . $e->getMessage()
    ], 500);
}
?>