<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Klinik</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🏥 Sistem Manajemen Klinik</h1>
            <nav class="navbar">
                <a href="#" onclick="showSection('dashboard')">📊 Dashboard</a>
                <a href="#" onclick="showSection('appointments')">📅 Janji Temu</a>
                <a href="#" onclick="showSection('patients')">👥 Pasien</a>
                <a href="#" onclick="showSection('doctors')">👨‍⚕️ Dokter</a>
                <a href="#" onclick="showSection('medicines')">💊 Obat</a>
                <a href="#" onclick="showSection('invoices')">🧾 Invoice</a>
            </nav>
        </header>

        <main class="main-content">
            <!-- Alert Container -->
            <div id="alert-container"></div>

            <!-- Dashboard Section -->
            <section id="dashboard" class="section active">
                <h2>Dashboard Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Pasien</h3>
                        <p id="total-patients">0</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Dokter</h3>
                        <p id="total-doctors">0</p>
                    </div>
                    <div class="stat-card">
                        <h3>Janji Hari Ini</h3>
                        <p id="today-appointments">0</p>
                    </div>
                    <div class="stat-card">
                        <h3>Obat Stok Rendah</h3>
                        <p id="low-stock">0</p>
                    </div>
                </div>

                <!-- Recent Data -->
                <div class="recent-data">
                    <div class="recent-card">
                        <h3>Pasien Terbaru</h3>
                        <div id="recent-patients"></div>
                    </div>
                    <div class="recent-card">
                        <h3>Janji Temu Mendatang</h3>
                        <div id="recent-appointments"></div>
                    </div>
                    <div class="recent-card">
                        <h3>Obat Stok Rendah</h3>
                        <div id="low-stock-medicines"></div>
                    </div>
                </div>
            </section>

            <!-- Appointments Section -->
            <section id="appointments" class="section">
                <div class="section-header">
                    <h2>Manajemen Janji Temu</h2>
                    <button class="btn-primary" onclick="showAppointmentForm()">+ Tambah Janji</button>
                </div>
                
                <!-- Appointment Form -->
                <div id="appointment-form" class="form-container" style="display: none;">
                    <h3 id="appointment-form-title">Tambah Janji Baru</h3>
                    <form id="appointmentForm">
                        <input type="hidden" id="appointment_id" name="id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="patient_id">Pasien:</label>
                                <select id="patient_id" name="patient_id" required>
                                    <option value="">Pilih Pasien</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="doctor_id">Dokter:</label>
                                <select id="doctor_id" name="doctor_id" required>
                                    <option value="">Pilih Dokter</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="room_id">Ruangan:</label>
                                <select id="room_id" name="room_id" required>
                                    <option value="">Pilih Ruangan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="scheduled_at">Tanggal & Waktu:</label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="appointment_type">Tipe Janji:</label>
                                <select id="appointment_type" name="type">
                                    <option value="consultation">Konsultasi</option>
                                    <option value="treatment">Perawatan</option>
                                    <option value="surgery">Operasi</option>
                                    <option value="checkup">Pemeriksaan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Status:</label>
                                <select id="status" name="status">
                                    <option value="scheduled">Terjadwal</option>
                                    <option value="completed">Selesai</option>
                                    <option value="cancelled">Dibatalkan</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="complaint">Keluhan:</label>
                            <textarea id="complaint" name="complaint" rows="3" placeholder="Masukkan keluhan pasien"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="appointment_notes">Catatan:</label>
                            <textarea id="appointment_notes" name="notes" rows="3" placeholder="Masukkan catatan tambahan"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Simpan</button>
                            <button type="button" class="btn-secondary" onclick="hideAppointmentForm()">Batal</button>
                        </div>
                    </form>
                </div>

                <div id="appointments-list" class="table-container"></div>
            </section>

            <!-- Patients Section -->
            <section id="patients" class="section">
                <div class="section-header">
                    <h2>Manajemen Pasien</h2>
                    <button class="btn-primary" onclick="showPatientForm()">+ Tambah Pasien</button>
                </div>
                
                <!-- Patient Form -->
                <div id="patient-form" class="form-container" style="display: none;">
                    <h3 id="patient-form-title">Tambah Pasien Baru</h3>
                    <form id="patientForm">
                        <input type="hidden" id="patient_id" name="id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="medical_record_number">No. Rekam Medis:</label>
                                <input type="text" id="medical_record_number" name="medical_record_number" required>
                            </div>
                            <div class="form-group">
                                <label for="full_name">Nama Lengkap:</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Jenis Kelamin:</label>
                                <select id="gender" name="gender" required>
                                    <option value="M">Laki-laki</option>
                                    <option value="F">Perempuan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="birth_date">Tanggal Lahir:</label>
                                <input type="date" id="birth_date" name="birth_date" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Telepon:</label>
                                <input type="text" id="phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="address">Alamat:</label>
                            <textarea id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_type">Golongan Darah:</label>
                                <select id="blood_type" name="blood_type">
                                    <option value="">Pilih Golongan Darah</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="AB">AB</option>
                                    <option value="O">O</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="emergency_contact">Kontak Darurat:</label>
                                <input type="text" id="emergency_contact" name="emergency_contact">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="allergies">Alergi:</label>
                            <textarea id="allergies" name="allergies" rows="2" placeholder="Daftar alergi pasien (jika ada)"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Simpan</button>
                            <button type="button" class="btn-secondary" onclick="hidePatientForm()">Batal</button>
                        </div>
                    </form>
                </div>

                <div id="patients-list" class="table-container"></div>
            </section>

            <!-- Doctors Section -->
            <section id="doctors" class="section">
                <div class="section-header">
                    <h2>Manajemen Dokter</h2>
                    <button class="btn-primary" onclick="showDoctorForm()">+ Tambah Dokter</button>
                </div>
                
                <!-- Doctor Form -->
                <div id="doctor-form" class="form-container" style="display: none;">
                    <h3 id="doctor-form-title">Tambah Dokter Baru</h3>
                    <form id="doctorForm">
                        <input type="hidden" id="doctor_id" name="id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor_full_name">Nama Lengkap:</label>
                                <input type="text" id="doctor_full_name" name="full_name" required>
                            </div>
                            <div class="form-group">
                                <label for="specialization">Spesialisasi:</label>
                                <input type="text" id="specialization" name="specialization" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor_phone">Telepon:</label>
                                <input type="text" id="doctor_phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="doctor_email">Email:</label>
                                <input type="email" id="doctor_email" name="email">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="license_number">No. Lisensi:</label>
                                <input type="text" id="license_number" name="license_number">
                            </div>
                            <div class="form-group">
                                <label for="experience_years">Pengalaman (tahun):</label>
                                <input type="number" id="experience_years" name="experience_years" min="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="education">Pendidikan:</label>
                            <textarea id="education" name="education" rows="2" placeholder="Riwayat pendidikan"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="schedule">Jadwal Praktik:</label>
                            <textarea id="schedule" name="schedule" rows="2" placeholder="Contoh: Senin-Jumat 08:00-16:00"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="notes">Catatan:</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Catatan tambahan"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Simpan</button>
                            <button type="button" class="btn-secondary" onclick="hideDoctorForm()">Batal</button>
                        </div>
                    </form>
                </div>

                <div id="doctors-list" class="table-container"></div>
            </section>

            <!-- Medicines Section -->
            <section id="medicines" class="section">
                <div class="section-header">
                    <h2>Manajemen Obat</h2>
                    <button class="btn-primary" onclick="showMedicineForm()">+ Tambah Obat</button>
                </div>
                
                <!-- Medicine Form -->
                <div id="medicine-form" class="form-container" style="display: none;">
                    <h3 id="medicine-form-title">Tambah Obat Baru</h3>
                    <form id="medicineForm">
                        <input type="hidden" id="medicine_id" name="id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="code">Kode Obat:</label>
                                <input type="text" id="code" name="code" required>
                            </div>
                            <div class="form-group">
                                <label for="medicine_name">Nama Obat:</label>
                                <input type="text" id="medicine_name" name="name" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="generic_name">Nama Generik:</label>
                                <input type="text" id="generic_name" name="generic_name">
                            </div>
                            <div class="form-group">
                                <label for="category">Kategori:</label>
                                <input type="text" id="category" name="category">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="unit">Satuan:</label>
                                <input type="text" id="unit" name="unit" required>
                            </div>
                            <div class="form-group">
                                <label for="price">Harga:</label>
                                <input type="number" id="price" name="price" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock">Stok:</label>
                                <input type="number" id="stock" name="stock" required>
                            </div>
                            <div class="form-group">
                                <label for="min_stock">Stok Minimum:</label>
                                <input type="number" id="min_stock" name="min_stock" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="supplier">Supplier:</label>
                                <input type="text" id="supplier" name="supplier">
                            </div>
                            <div class="form-group">
                                <label for="expiry_date">Tanggal Kadaluarsa:</label>
                                <input type="date" id="expiry_date" name="expiry_date">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Deskripsi:</label>
                            <textarea id="description" name="description" rows="3" placeholder="Deskripsi obat"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Simpan</button>
                            <button type="button" class="btn-secondary" onclick="hideMedicineForm()">Batal</button>
                        </div>
                    </form>
                </div>

                <div id="medicines-list" class="table-container"></div>
            </section>

            <!-- Invoices Section -->
            <section id="invoices" class="section">
                <div class="section-header">
                    <h2>Manajemen Invoice</h2>
                    <button class="btn-primary" onclick="showInvoiceForm()">+ Buat Invoice</button>
                </div>
                
                <!-- Invoice Form -->
                <div id="invoice-form" class="form-container" style="display: none;">
                    <h3 id="invoice-form-title">Buat Invoice Baru</h3>
                    <form id="invoiceForm">
                        <input type="hidden" id="invoice_id" name="id">
                        <div class="form-group">
                            <label for="invoice_appointment_id">Pilih Janji Temu:</label>
                            <select id="invoice_appointment_id" name="appointment_id" required>
                                <option value="">Pilih Janji Temu</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="invoice_total_amount">Total Amount:</label>
                            <input type="number" id="invoice_total_amount" name="total_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Metode Pembayaran:</label>
                            <select id="payment_method" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="debit_card">Kartu Debit</option>
                                <option value="credit_card">Kartu Kredit</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Buat Invoice</button>
                            <button type="button" class="btn-secondary" onclick="hideInvoiceForm()">Batal</button>
                        </div>
                    </form>
                </div>

                <!-- Payment Modal -->
                <div id="paymentModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <h3>Pembayaran Invoice</h3>
                        <form id="paymentForm">
                            <input type="hidden" id="payment_invoice_id">
                            <div class="form-group">
                                <label>No. Invoice:</label>
                                <input type="text" id="payment_invoice_number" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Amount:</label>
                                <input type="text" id="payment_total" readonly>
                            </div>
                            <div class="form-group">
                                <label>Already Paid:</label>
                                <input type="text" id="payment_paid" readonly>
                            </div>
                            <div class="form-group">
                                <label>Amount to Pay:</label>
                                <input type="number" id="payment_amount" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Metode Pembayaran:</label>
                                <select id="payment_payment_method">
                                    <option value="cash">Cash</option>
                                    <option value="debit_card">Kartu Debit</option>
                                    <option value="credit_card">Kartu Kredit</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Proses Pembayaran</button>
                                <button type="button" class="btn-secondary" onclick="closePaymentModal()">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="invoices-list" class="table-container"></div>
            </section>
        </main>
    </div>

    <script src="js/app.js"></script>
</body>
</html>