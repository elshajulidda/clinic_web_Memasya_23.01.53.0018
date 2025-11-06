-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 05:00 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `type` enum('consultation','treatment','surgery','checkup') DEFAULT 'consultation',
  `complaint` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `room_id`, `scheduled_at`, `status`, `type`, `complaint`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 1, '2024-12-10 09:00:00', 'scheduled', 'consultation', 'Pemeriksaan rutin dan konsultasi hasil lab', 'Pasien datang untuk follow up', 6, '2024-12-09 08:00:00'),
(2, 2, 2, 3, '2024-12-10 10:30:00', 'scheduled', 'treatment', 'Sakit gigi geraham belakang kanan', 'Perlu dilakukan penambalan', 6, '2024-12-09 08:00:00'),
(3, 3, 1, 2, '2024-12-09 14:00:00', 'completed', 'consultation', 'Demam dan batuk sudah 3 hari', 'Pasien datang dengan keluhan ISPA', 6, '2024-12-08 10:00:00'),
(4, 4, 2, 3, '2024-12-09 11:00:00', 'completed', 'treatment', 'Gusi bengkak dan berdarah', 'Perawatan pembersihan karang gigi', 6, '2024-12-08 11:00:00'),
(5, 5, 3, 7, '2024-12-11 13:00:00', 'scheduled', 'checkup', 'Imunisasi anak usia 2 tahun', 'Imunisasi DPT dan polio', 6, '2024-12-09 08:00:00'),
(6, 6, 1, 1, '2024-12-09 16:00:00', 'completed', 'consultation', 'Pusing dan lemas berkepanjangan', 'Perlu pemeriksaan tekanan darah', 6, '2024-12-08 14:00:00'),
(7, 7, 2, 6, '2024-12-12 15:30:00', 'scheduled', 'treatment', 'Pemasangan kawat gigi kontrol', 'Kontrol rutin 3 bulan sekali', 6, '2024-12-09 08:00:00'),
(8, 8, 1, 2, '2024-12-11 10:00:00', 'scheduled', 'consultation', 'Konsultasi hasil medical checkup', 'Membahas hasil laboratorium', 6, '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`id`, `name`, `address`, `phone`, `email`, `created_at`) VALUES
(1, 'Klinik Utama Sehat Bahagia', 'Jl. Melati Raya No. 123, Jakarta Pusat', '021-1234567', 'info@kliniksehatbahagia.com', '2024-12-09 08:00:00'),
(2, 'Klinik Gigi Senyum Sehat', 'Jl. Mawar Indah No. 45, Bandung', '022-7654321', 'contact@senyumsehat.com', '2024-12-09 08:00:00'),
(3, 'Klinik Anak Ceria', 'Jl. Anggrek No. 78, Surabaya', '031-8889999', 'hello@anakceria.com', '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `education` text DEFAULT NULL,
  `schedule` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `license_number`, `experience_years`, `education`, `schedule`, `is_available`, `created_at`) VALUES
(1, 2, 'Penyakit Dalam', 'SIP.12345/2020', 8, 'Universitas Indonesia - Spesialis Penyakit Dalam', 'Senin-Jumat: 08:00-15:00', 1, '2024-12-09 08:00:00'),
(2, 3, 'Kedokteran Gigi', 'SIP.12346/2019', 10, 'Universitas Gadjah Mada - Spesialis Kedokteran Gigi', 'Senin-Sabtu: 09:00-17:00', 1, '2024-12-09 08:00:00'),
(3, 4, 'Anak', 'SIP.12347/2021', 6, 'Universitas Airlangga - Spesialis Anak', 'Senin-Jumat: 10:00-16:00', 1, '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `payment_method` enum('cash','debit_card','credit_card','transfer','qris') DEFAULT 'cash',
  `payment_status` enum('unpaid','paid','partial','cancelled') DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `appointment_id`, `patient_id`, `invoice_number`, `total_amount`, `paid_amount`, `payment_method`, `payment_status`, `due_date`, `paid_at`, `created_by`, `created_at`) VALUES
(1, 3, 3, 'INV-20241209-001', 185000.00, 185000.00, 'cash', 'paid', '2024-12-16', '2024-12-09 15:00:00', 8, '2024-12-09 14:40:00'),
(2, 4, 4, 'INV-20241209-002', 350000.00, 200000.00, 'debit_card', 'partial', '2024-12-16', '2024-12-09 12:00:00', 8, '2024-12-09 11:40:00'),
(3, 6, 6, 'INV-20241209-003', 120000.00, 120000.00, 'qris', 'paid', '2024-12-16', '2024-12-09 17:00:00', 8, '2024-12-09 16:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `item_type` enum('consultation','medicine','treatment','lab','other') DEFAULT 'consultation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total_price`, `item_type`) VALUES
(1, 1, 'Biaya Konsultasi Dokter Spesialis', 1, 150000.00, 150000.00, 'consultation'),
(2, 1, 'Paracetamol 500mg - 10 tablet', 1, 5000.00, 5000.00, 'medicine'),
(3, 1, 'Cetirizine 10mg - 5 tablet', 1, 8000.00, 8000.00, 'medicine'),
(4, 1, 'Vitamin C 500mg - 10 tablet', 1, 3000.00, 3000.00, 'medicine'),
(5, 1, 'Administrasi', 1, 19000.00, 19000.00, 'other'),
(6, 2, 'Biaya Perawatan Gigi (Scaling)', 1, 300000.00, 300000.00, 'treatment'),
(7, 2, 'Ibuprofen 400mg - 7 tablet', 1, 6000.00, 6000.00, 'medicine'),
(8, 2, 'Vitamin C 500mg - 5 tablet', 1, 3000.00, 3000.00, 'medicine'),
(9, 2, 'Obat Kumur', 1, 41000.00, 41000.00, 'medicine'),
(10, 3, 'Biaya Konsultasi Dokter Spesialis', 1, 150000.00, 150000.00, 'consultation'),
(11, 3, 'Omeprazole 20mg - 30 kapsul', 1, 12000.00, 120000.00, 'medicine'),
(12, 3, 'Administrasi', 1, -42000.00, -42000.00, 'other');

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `test_name` varchar(150) DEFAULT NULL,
  `test_type` varchar(100) DEFAULT NULL,
  `result` text DEFAULT NULL,
  `normal_range` varchar(100) DEFAULT NULL,
  `units` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`id`, `medical_record_id`, `patient_id`, `test_name`, `test_type`, `result`, `normal_range`, `units`, `notes`, `performed_by`, `performed_at`, `created_at`) VALUES
(1, 3, 3, 'Hemoglobin (Hb)', 'Darah Rutin', '14.2', '13.5-17.5', 'g/dL', 'Dalam batas normal', 9, '2024-12-09 14:45:00', '2024-12-09 14:45:00'),
(2, 3, 3, 'Leukosit (WBC)', 'Darah Rutin', '12.5', '4.5-11.0', '10^3/?L', 'Sedikit meningkat, sesuai dengan infeksi', 9, '2024-12-09 14:45:00', '2024-12-09 14:45:00'),
(3, 6, 6, 'Tekanan Darah', 'Pemeriksaan Fisik', '90/60', '100/60-120/80', 'mmHg', 'Hipotensi ringan', 5, '2024-12-09 16:45:00', '2024-12-09 16:45:00'),
(4, 6, 6, 'Gula Darah Puasa', 'Kimia Darah', '95', '70-100', 'mg/dL', 'Dalam batas normal', 9, '2024-12-09 16:50:00', '2024-12-09 16:50:00');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `complaint` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `vital_signs` text DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `appointment_id`, `patient_id`, `doctor_id`, `complaint`, `diagnosis`, `treatment`, `notes`, `vital_signs`, `height`, `weight`, `blood_pressure`, `temperature`, `created_at`) VALUES
(1, 3, 3, 1, 'Demam dan batuk sudah 3 hari', 'ISPA (Infeksi Saluran Pernapasan Akut)', 'Istirahat yang cukup, minum air putih, dan obat simptomatik', 'Pasien disarankan kembali jika demam tidak turun dalam 3 hari', 'TD: 120/80, N: 80x/m, RR: 20x/m', 170.00, 68.00, '120/80', 38.20, '2024-12-09 14:30:00'),
(2, 4, 4, 2, 'Gusi bengkak dan berdarah', 'Gingivitis (Radang Gusi)', 'Scaling dan pemberian obat kumur', 'Ajarkan teknik menyikat gigi yang benar', 'TD: 110/70, N: 75x/m, RR: 18x/m', 165.00, 55.00, '110/70', 36.80, '2024-12-09 11:30:00'),
(3, 6, 6, 1, 'Pusing dan lemas berkepanjangan', 'Hipotensi (Tekanan Darah Rendah)', 'Peningkatan asupan garam dan cairan', 'Monitor tekanan darah secara rutin', 'TD: 90/60, N: 85x/m, RR: 22x/m', 175.00, 70.00, '90/60', 36.50, '2024-12-09 16:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `generic_name` varchar(150) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 0,
  `supplier` varchar(150) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `code`, `name`, `generic_name`, `category`, `unit`, `price`, `stock`, `min_stock`, `supplier`, `expiry_date`, `description`, `is_active`, `created_at`) VALUES
(1, 'OBT001', 'Paracetamol 500mg', 'Paracetamol', 'Analgesik & Antipiretik', 'tablet', 5000.00, 150, 20, 'PT. Kimia Farma', '2025-12-31', 'Obat penurun demam dan pereda nyeri', 1, '2024-12-09 08:00:00'),
(2, 'OBT002', 'Amoxicillin 500mg', 'Amoxicillin', 'Antibiotik', 'kapsul', 15000.00, 80, 15, 'PT. Kalbe Farma', '2025-06-30', 'Antibiotik untuk infeksi bakteri', 1, '2024-12-09 08:00:00'),
(3, 'OBT003', 'Cetirizine 10mg', 'Cetirizine', 'Antihistamin', 'tablet', 8000.00, 100, 10, 'PT. Dexa Medica', '2025-09-15', 'Obat alergi dan gatal-gatal', 1, '2024-12-09 08:00:00'),
(4, 'OBT004', 'Omeprazole 20mg', 'Omeprazole', 'Antasida & Antiulser', 'kapsul', 12000.00, 60, 12, 'PT. Sanbe Farma', '2025-08-20', 'Obat maag dan asam lambung', 1, '2024-12-09 08:00:00'),
(5, 'OBT005', 'Vitamin C 500mg', 'Ascorbic Acid', 'Vitamin & Suplemen', 'tablet', 3000.00, 200, 30, 'PT. Soho Global', '2025-11-10', 'Suplemen vitamin C', 1, '2024-12-09 08:00:00'),
(6, 'OBT006', 'Metformin 500mg', 'Metformin', 'Antidiabetes', 'tablet', 7000.00, 90, 15, 'PT. Novell Pharma', '2025-07-25', 'Obat diabetes tipe 2', 1, '2024-12-09 08:00:00'),
(7, 'OBT007', 'Salbutamol Inhaler', 'Salbutamol', 'Bronkodilator', 'pcs', 45000.00, 30, 5, 'PT. Guardian Pharmatama', '2025-05-15', 'Obat asma dan sesak napas', 1, '2024-12-09 08:00:00'),
(8, 'OBT008', 'Ibuprofen 400mg', 'Ibuprofen', 'Anti Inflamasi', 'tablet', 6000.00, 120, 18, 'PT. Tempo Scan Pacific', '2025-10-05', 'Obat anti radang dan nyeri', 1, '2024-12-09 08:00:00'),
(9, 'OBT009', 'Loratadine 10mg', 'Loratadine', 'Antihistamin', 'tablet', 9000.00, 70, 10, 'PT. Dexa Medica', '2025-08-30', 'Obat alergi non-mengantuk', 1, '2024-12-09 08:00:00'),
(10, 'OBT010', 'Simvastatin 20mg', 'Simvastatin', 'Antikolesterol', 'tablet', 10000.00, 50, 8, 'PT. Kalbe Farma', '2025-09-20', 'Obat penurun kolesterol', 1, '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `medical_record_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `gender` enum('M','F') DEFAULT 'M',
  `birth_date` date DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `blood_type` enum('A','B','AB','O') DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `medical_record_number`, `full_name`, `gender`, `birth_date`, `phone`, `email`, `address`, `blood_type`, `allergies`, `emergency_contact`, `created_at`) VALUES
(1, 'RM0001', 'Sinta Dewi', 'F', '1990-05-20', '081234111222', 'sinta.dewi@gmail.com', 'Jl. Melati No. 10, Jakarta Pusat', 'A', 'Penicillin, Udang', 'Ayah: 081333444555', '2024-12-09 08:00:00'),
(2, 'RM0002', 'Andi Saputra', 'M', '1985-08-12', '08134567890', 'andi.saputra@yahoo.com', 'Jl. Mawar No. 22, Bandung', 'B', 'Debu, Tungau', 'Isteri: 081366777888', '2024-12-09 08:00:00'),
(3, 'RM0003', 'Budi Santoso', 'M', '1978-12-03', '08151234567', 'budi.santoso@gmail.com', 'Jl. Anggrek No. 15, Surabaya', 'O', 'Tidak ada', 'Anak: 081377888999', '2024-12-09 08:00:00'),
(4, 'RM0004', 'Maya Sari', 'F', '1992-03-25', '08162345678', 'maya.sari@email.com', 'Jl. Kenanga No. 8, Bogor', 'AB', 'Kacang, Susu', 'Suami: 081388999000', '2024-12-09 08:00:00'),
(5, 'RM0005', 'Rina Wati', 'F', '1988-07-18', '08173456789', 'rina.wati@gmail.com', 'Jl. Flamboyan No. 3, Depok', 'A', 'Ikan Laut', 'Ayah: 081399000111', '2024-12-09 08:00:00'),
(6, 'RM0006', 'Ahmad Fauzi', 'M', '1995-11-30', '08184567890', 'ahmad.fauzi@email.com', 'Jl. Cempaka No. 25, Tangerang', 'B', 'Tidak ada', 'Ibu: 081300111222', '2024-12-09 08:00:00'),
(7, 'RM0007', 'Dewi Lestari', 'F', '1982-04-15', '08195678901', 'dewi.lestari@gmail.com', 'Jl. Teratai No. 12, Bekasi', 'O', 'Telur, Kacang', 'Suami: 081311222333', '2024-12-09 08:00:00'),
(8, 'RM0008', 'Joko Widodo', 'M', '1975-09-22', '08206789012', 'joko.widodo@email.com', 'Jl. Seroja No. 7, Jakarta Selatan', 'A', 'Obat Anti Nyeri', 'Anak: 082122233344', '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `instructions` text DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `medical_record_id`, `patient_id`, `doctor_id`, `issued_by`, `issued_at`, `instructions`, `status`) VALUES
(1, 1, 3, 1, 2, '2024-12-09 14:35:00', 'Diminum 3x1 sehari setelah makan. Hindari alkohol.', 'active'),
(2, 2, 4, 2, 3, '2024-12-09 11:35:00', 'Berkumur 2x1 sehari setelah menyikat gigi. Hindari makanan panas dan dingin.', 'active'),
(3, 3, 6, 1, 2, '2024-12-09 16:35:00', 'Diminum 1x1 sehari pagi hari sebelum makan.', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`id`, `prescription_id`, `medicine_id`, `quantity`, `dosage`, `frequency`, `duration`, `notes`) VALUES
(1, 1, 1, 10, '1 tablet', '3 times daily', '3 days', 'Jika demam >38.5?C'),
(2, 1, 3, 5, '1 tablet', '1 time daily', '5 days', 'Sebelum tidur'),
(3, 1, 5, 10, '1 tablet', '1 time daily', '10 days', 'Setelah makan'),
(4, 2, 8, 7, '1 tablet', '2 times daily', '3 days', 'Jika nyeri'),
(5, 2, 5, 5, '1 tablet', '1 time daily', '5 days', 'Pagi hari'),
(6, 3, 4, 30, '1 kapsul', '1 time daily', '30 days', 'Sebelum sarapan');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'Administrator dengan akses penuh sistem', '2024-12-09 08:00:00'),
(2, 'dokter', 'Dokter yang melayani pasien', '2024-12-09 08:00:00'),
(3, 'perawat', 'Perawat yang membantu dokter', '2024-12-09 08:00:00'),
(4, 'resepsionis', 'Petugas pendaftaran pasien', '2024-12-09 08:00:00'),
(5, 'apoteker', 'Bagian farmasi dan obat-obatan', '2024-12-09 08:00:00'),
(6, 'kasir', 'Bagian keuangan dan pembayaran', '2024-12-09 08:00:00'),
(7, 'laboran', 'Petugas laboratorium', '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `room_type` enum('consultation','treatment','operation','emergency','pharmacy') DEFAULT 'consultation',
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `clinic_id`, `name`, `room_type`, `description`, `is_available`, `created_at`) VALUES
(1, 1, 'Ruang Konsultasi 1', 'consultation', 'Ruang konsultasi umum', 1, '2024-12-09 08:00:00'),
(2, 1, 'Ruang Konsultasi 2', 'consultation', 'Ruang konsultasi khusus', 1, '2024-12-09 08:00:00'),
(3, 1, 'Ruang Perawatan Gigi', 'treatment', 'Ruang perawatan dan pencabutan gigi', 1, '2024-12-09 08:00:00'),
(4, 1, 'Ruang Emergency', 'emergency', 'Ruang gawat darurat', 1, '2024-12-09 08:00:00'),
(5, 1, 'Apotek', 'pharmacy', 'Ruang penyerahan obat', 1, '2024-12-09 08:00:00'),
(6, 2, 'Ruang Dental 1', 'treatment', 'Ruang perawatan gigi utama', 1, '2024-12-09 08:00:00'),
(7, 3, 'Ruang Anak', 'consultation', 'Ruang konsultasi anak dengan tema cerah', 1, '2024-12-09 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role_id`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'Administrator System', 'admin@klinik.com', '081234567890', 1, 1, '2024-12-09 08:30:00', '2024-12-09 08:00:00'),
(2, 'dr_maria', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'dr. Maria Setiawati, Sp.PD', 'maria@klinik.com', '081234567891', 2, 1, '2024-12-09 08:15:00', '2024-12-09 08:00:00'),
(3, 'dr_budi', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'dr. Budi Prasetyo, Sp.KG', 'budi@klinik.com', '081234567892', 2, 1, '2024-12-09 08:20:00', '2024-12-09 08:00:00'),
(4, 'dr_anita', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'dr. Anita Wijaya, Sp.A', 'anita@klinik.com', '081234567893', 2, 1, '2024-12-09 08:25:00', '2024-12-09 08:00:00'),
(5, 'sari_nurse', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'Sari, S.Kep', 'sari@klinik.com', '081234567894', 3, 1, NULL, '2024-12-09 08:00:00'),
(6, 'reception', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'Dewi Resepsionis', 'dewi@klinik.com', '081234567895', 4, 1, NULL, '2024-12-09 08:00:00'),
(7, 'pharmacy', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'Rudi Apoteker, S.Farm', 'rudi@klinik.com', '081234567896', 5, 1, NULL, '2024-12-09 08:00:00'),
(8, 'cashier', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'Budi Kasir', 'kasir@klinik.com', '081234567897', 6, 1, NULL, '2024-12-09 08:00:00'),
(9, 'lab_staff', '$2y$10$kKu7T3s2vP1ZtUIWv5Vv0O5ZBf0DkUtD1SyD8XhFjykh1H5vdd7Y6', 'Maya Laboran, A.Md.AK', 'maya@klinik.com', '081234567898', 7, 1, NULL, '2024-12-09 08:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_record_id` (`medical_record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_record_number` (`medical_record_number`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_record_id` (`medical_record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD CONSTRAINT `lab_tests_ibfk_1` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`),
  ADD CONSTRAINT `lab_tests_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `lab_tests_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`),
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
