// ==================== KONFIGURASI & DEKLARASI ====================
const API_BASE = 'http://localhost/clinic_web/api/';
let currentEditingId = null;

// ==================== UTILITY FUNCTIONS ====================
function debugLog(message, data = null) {
    console.log(`[DEBUG] ${message}`, data || '');
}

function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="loading" style="text-align: center; padding: 40px; color: #666;">
                <div>Memuat data...</div>
                <small>Silakan tunggu</small>
            </div>
        `;
    }
}

function showError(containerId, message) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="alert alert-error">
                <strong>Error:</strong> ${message}
                <br><small>Periksa koneksi database dan pastikan server berjalan.</small>
            </div>
        `;
    }
}

function showAlert(message, type = 'error') {
    // Hapus alert existing
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<strong>${type === 'error' ? 'Error:' : type === 'success' ? 'Sukses:' : 'Peringatan:'}</strong> ${message}`;
    
    const container = document.getElementById('alert-container') || document.querySelector('.main-content');
    if (container) {
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'N/A';
    try {
        const date = new Date(dateTimeStr);
        return date.toLocaleString('id-ID');
    } catch (e) {
        return 'Invalid Date';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID');
    } catch (e) {
        return 'Invalid Date';
    }
}

function formatCurrency(amount) {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}

function getStatusText(status) {
    const statusMap = {
        'scheduled': 'Terjadwal',
        'completed': 'Selesai',
        'cancelled': 'Dibatalkan',
        'no_show': 'Tidak Datang'
    };
    return statusMap[status] || status || 'Unknown';
}

function getInvoiceStatusText(status) {
    const statusMap = {
        'unpaid': 'Belum Bayar',
        'paid': 'Lunas',
        'partial': 'Sebagian',
        'cancelled': 'Dibatalkan'
    };
    return statusMap[status] || status || 'Unknown';
}

function safeUpdateElement(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
        console.log(`✅ Updated ${elementId}: ${text}`);
    } else {
        console.error(`❌ Element dengan ID '${elementId}' tidak ditemukan!`);
    }
}

function updateSelectOptions(selectId, data, primaryField, secondaryField) {
    const select = document.getElementById(selectId);
    if (select) {
        select.innerHTML = '<option value="">Pilih ' + selectId.replace('_', ' ') + '</option>' + 
            data.map(item => 
                `<option value="${item.id}">${item[primaryField]} - ${item[secondaryField]}</option>`
            ).join('');
    }
}

// ==================== API FUNCTIONS ====================
async function apiCall(endpoint, options = {}) {
    console.log(`🌐 API Call: ${endpoint}`, options.method || 'GET');
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000);

    try {
        const config = {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            signal: controller.signal
        };

        // Jika ada body, tambahkan ke config (kecuali untuk GET)
        if (options.body && config.method !== 'GET') {
            config.body = options.body;
        }

        console.log(`📡 Sending request to ${endpoint}`, {
            method: config.method,
            headers: config.headers,
            body: config.body ? JSON.parse(config.body) : 'No body'
        });

        const response = await fetch(API_BASE + endpoint, config);
        
        clearTimeout(timeoutId);
        
        console.log(`📄 Response status: ${response.status} ${response.statusText}`);
        
        if (!response.ok) {
            let errorMessage = `HTTP error! status: ${response.status}`;
            try {
                const errorText = await response.text();
                if (errorText) {
                    errorMessage += ` - ${errorText}`;
                }
            } catch (e) {
                // Ignore text parsing error
            }
            throw new Error(errorMessage);
        }
        
        const text = await response.text();
        console.log(`📨 Raw response from ${endpoint}:`, text.substring(0, 500));
        
        if (!text) {
            throw new Error('Empty response from server');
        }
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('❌ JSON parse error:', e);
            console.error('❌ Problematic response:', text);
            throw new Error(`Invalid JSON response: ${text.substring(0, 100)}`);
        }
        
        return data;
        
    } catch (error) {
        clearTimeout(timeoutId);
        
        if (error.name === 'AbortError') {
            throw new Error('Request timeout - server tidak merespon setelah 15 detik');
        }
        
        console.error(`❌ API Call Error for ${endpoint}:`, error);
        throw error;
    }
}

// ==================== DASHBOARD FUNCTIONS ====================
async function loadDashboard() {
    console.log('🚀 loadDashboard() started');
    
    try {
        const dashboardElement = document.getElementById('dashboard');
        if (!dashboardElement) {
            console.error('❌ Element dashboard tidak ditemukan!');
            return;
        }

        // Tampilkan loading
        dashboardElement.innerHTML = `
            <div class="loading" style="text-align: center; padding: 40px; color: #666;">
                <div>Memuat data dashboard...</div>
                <small>Silakan tunggu</small>
            </div>
        `;

        console.log('📊 Fetching dashboard data...');

        // Test koneksi dasar dulu
        try {
            const testResponse = await fetch(API_BASE + 'test_api.php');
            const testData = await testResponse.json();
            console.log('🧪 Test API Result:', testData);
        } catch (testError) {
            console.error('❌ Test API failed:', testError);
        }

        // Coba load data dengan approach yang lebih sederhana
        const endpoints = [
            { name: 'patients', url: 'patients.php' },
            { name: 'doctors', url: 'doctors.php' },
            { name: 'appointments', url: 'appointments.php' },
            { name: 'medicines', url: 'medicines.php' }
        ];

        const results = {};
        let hasData = false;

        for (const endpoint of endpoints) {
            try {
                console.log(`🔍 Fetching ${endpoint.name} from ${endpoint.url}...`);
                
                const response = await fetch(API_BASE + endpoint.url);
                console.log(`📡 Response status for ${endpoint.name}:`, response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const text = await response.text();
                console.log(`📄 Raw response for ${endpoint.name}:`, text.substring(0, 200));
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error(`❌ JSON parse error for ${endpoint.name}:`, parseError);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
                
                results[endpoint.name] = Array.isArray(data) ? data : [];
                console.log(`✅ ${endpoint.name} loaded:`, results[endpoint.name].length, 'items');
                
                if (results[endpoint.name].length > 0) {
                    hasData = true;
                }
                
            } catch (error) {
                console.error(`❌ Error fetching ${endpoint.name}:`, error);
                results[endpoint.name] = [];
                showAlert(`Error memuat data ${endpoint.name}: ${error.message}`, 'error');
            }
        }

        if (hasData) {
            // Update dashboard dengan data yang berhasil diambil
            updateDashboardWithData(results);
        } else {
            // Coba gunakan data dummy
            try {
                console.log('🔄 Trying to load dummy data...');
                const dummyData = await loadDummyData();
                updateDashboardWithData(dummyData);
                showAlert('Menggunakan data contoh. Periksa koneksi database.', 'warning');
            } catch (dummyError) {
                console.error('❌ Dummy data also failed:', dummyError);
                showError('dashboard', 'Tidak dapat memuat data dari server. Periksa koneksi database dan pastikan API endpoints berjalan.');
            }
        }
        
        console.log('✅ Dashboard loading completed');

    } catch (error) {
        console.error('❌ Critical error in loadDashboard:', error);
        showError('dashboard', 'Error kritis memuat dashboard: ' + error.message);
    }
}

function updateDashboardWithData(data) {
    const { patients = [], doctors = [], appointments = [], medicines = [] } = data;
    
    console.log('📈 Updating dashboard with:', {
        patients: patients.length,
        doctors: doctors.length,
        appointments: appointments.length,
        medicines: medicines.length
    });

    // Today's appointments
    const today = new Date().toISOString().split('T')[0];
    const todayApps = appointments.filter(apt => 
        apt.scheduled_at && apt.scheduled_at.startsWith(today)
    );

    // Low stock medicines
    const lowStockMedicines = medicines.filter(med => 
        med.stock <= (med.min_stock || 0)
    );

    // Update stats - gunakan innerHTML langsung
    const dashboardHTML = `
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Pasien</h3>
                <div class="stat-number">${patients.length}</div>
            </div>
            <div class="stat-card">
                <h3>Total Dokter</h3>
                <div class="stat-number">${doctors.length}</div>
            </div>
            <div class="stat-card">
                <h3>Janji Hari Ini</h3>
                <div class="stat-number">${todayApps.length}</div>
            </div>
            <div class="stat-card">
                <h3>Stok Rendah</h3>
                <div class="stat-number">${lowStockMedicines.length}</div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="recent-section">
                <h3>Pasien Terbaru</h3>
                <div id="recent-patients">
                    ${renderRecentPatients(patients.slice(0, 5))}
                </div>
            </div>
            
            <div class="recent-section">
                <h3>Janji Mendatang</h3>
                <div id="recent-appointments">
                    ${renderRecentAppointments(appointments.slice(0, 5))}
                </div>
            </div>
            
            <div class="recent-section">
                <h3>Obat Stok Rendah</h3>
                <div id="low-stock-medicines">
                    ${renderLowStockMedicines(medicines.slice(0, 5))}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dashboard').innerHTML = dashboardHTML;
}

function renderRecentPatients(patients) {
    if (patients.length === 0) {
        return '<div class="recent-item">Tidak ada data pasien</div>';
    }
    
    return patients.map(patient => `
        <div class="recent-item">
            <div class="info">
                <strong>${patient.medical_record_number || 'N/A'}</strong>
                <div>${patient.full_name || 'N/A'}</div>
            </div>
        </div>
    `).join('');
}

function renderRecentAppointments(appointments) {
    const upcoming = appointments.filter(apt => apt.status === 'scheduled').slice(0, 5);
    
    if (upcoming.length === 0) {
        return '<div class="recent-item">Tidak ada janji temu mendatang</div>';
    }
    
    return upcoming.map(apt => `
        <div class="recent-item">
            <div class="info">
                <strong>${apt.patient_name || 'N/A'}</strong>
                <div>${formatDateTime(apt.scheduled_at)}</div>
                <small>${apt.doctor_name || 'N/A'}</small>
            </div>
            <span class="badge status-${apt.status}">${getStatusText(apt.status)}</span>
        </div>
    `).join('');
}

function renderLowStockMedicines(medicines) {
    const lowStock = medicines.filter(med => med.stock <= (med.min_stock || 0));
    
    if (lowStock.length === 0) {
        return '<div class="recent-item">Tidak ada obat dengan stok rendah</div>';
    }
    
    return lowStock.map(med => `
        <div class="recent-item">
            <div class="info">
                <strong>${med.name || 'N/A'}</strong>
                <div>Stok: ${med.stock || 0} (Min: ${med.min_stock || 0})</div>
            </div>
            <span class="badge stock-low">Stok Rendah</span>
        </div>
    `).join('');
}

async function loadDummyData() {
    try {
        const response = await fetch(API_BASE + 'dummy_data.php');
        const data = await response.json();
        
        return {
            patients: data.patients || [],
            doctors: data.doctors || [],
            appointments: data.appointments || [],
            medicines: data.medicines || []
        };
    } catch (error) {
        // Fallback ke static dummy data jika file tidak ada
        console.log('📋 Using static dummy data');
        return {
            patients: [
                {
                    "id": 1,
                    "medical_record_number": "RM0001",
                    "full_name": "Sinta Dewi",
                    "gender": "F",
                    "birth_date": "1990-05-20",
                    "phone": "081234111222",
                    "email": "sinta.dewi@gmail.com",
                    "address": "Jl. Melati No. 10, Jakarta Pusat"
                },
                {
                    "id": 2,
                    "medical_record_number": "RM0002", 
                    "full_name": "Andi Saputra",
                    "gender": "M",
                    "birth_date": "1985-08-12",
                    "phone": "08134567890",
                    "email": "andi.saputra@yahoo.com",
                    "address": "Jl. Mawar No. 22, Bandung"
                }
            ],
            doctors: [
                {
                    "id": 1,
                    "full_name": "dr. Maria Setiawati, Sp.PD",
                    "specialization": "Penyakit Dalam",
                    "phone": "081234567891",
                    "license_number": "SIP.12345/2020",
                    "experience_years": 8
                },
                {
                    "id": 2,
                    "full_name": "dr. Budi Prasetyo, Sp.KG",
                    "specialization": "Kedokteran Gigi", 
                    "phone": "081234567892",
                    "license_number": "SIP.12346/2019",
                    "experience_years": 10
                }
            ],
            appointments: [
                {
                    "id": 1,
                    "patient_name": "Sinta Dewi",
                    "doctor_name": "dr. Maria Setiawati, Sp.PD", 
                    "room_name": "Ruang Konsultasi 1",
                    "scheduled_at": "2024-12-10 09:00:00",
                    "status": "scheduled",
                    "type": "consultation"
                },
                {
                    "id": 2,
                    "patient_name": "Andi Saputra",
                    "doctor_name": "dr. Budi Prasetyo, Sp.KG",
                    "room_name": "Ruang Perawatan Gigi", 
                    "scheduled_at": "2024-12-10 10:30:00",
                    "status": "scheduled",
                    "type": "treatment"
                }
            ],
            medicines: [
                {
                    "id": 1,
                    "code": "OBT001",
                    "name": "Paracetamol 500mg",
                    "unit": "tablet",
                    "price": 5000,
                    "stock": 150,
                    "min_stock": 20
                },
                {
                    "id": 2, 
                    "code": "OBT002",
                    "name": "Amoxicillin 500mg",
                    "unit": "kapsul", 
                    "price": 15000,
                    "stock": 5,  // Stok rendah
                    "min_stock": 15
                }
            ]
        };
    }
}

// ==================== NAVIGATION ====================
function showSection(sectionId) {
    console.log(`🔄 Switching to section: ${sectionId}`);
    
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
        console.log(`✅ Section ${sectionId} activated`);
    } else {
        console.error(`❌ Section ${sectionId} not found!`);
    }
    
    // Load data for the section
    switch(sectionId) {
        case 'dashboard':
            console.log('🔄 Loading dashboard data...');
            loadDashboard();
            break;
        case 'appointments':
            loadAppointments();
            loadFormOptions();
            break;
        case 'patients':
            loadPatients();
            break;
        case 'doctors':
            loadDoctors();
            break;
        case 'medicines':
            loadMedicines();
            break;
        case 'invoices':
            loadInvoices();
            loadInvoiceFormOptions();
            break;
    }
}

// ==================== FORM OPTIONS FUNCTIONS ====================
async function loadFormOptions() {
    try {
        const [patients, doctors, rooms] = await Promise.all([
            apiCall('patients.php').catch(() => []),
            apiCall('doctors.php').catch(() => []),
            apiCall('rooms.php').catch(() => [])
        ]);

        updateSelectOptions('patient_id', patients, 'medical_record_number', 'full_name');
        updateSelectOptions('doctor_id', doctors, 'full_name', 'specialization');
        updateSelectOptions('room_id', rooms, 'name', 'clinic_name');
    } catch (error) {
        console.error('Error loading form options:', error);
    }
}

async function loadInvoiceFormOptions() {
    try {
        const appointments = await apiCall('appointments.php');
        const select = document.getElementById('invoice_appointment_id');
        
        if (select) {
            select.innerHTML = '<option value="">Pilih Janji Temu</option>' + 
                appointments.filter(apt => apt.status === 'completed').map(apt => 
                    `<option value="${apt.id}">${apt.patient_name} - ${formatDateTime(apt.scheduled_at)}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading invoice options:', error);
    }
}

// ==================== EDIT FUNCTIONS ====================
function editPatient(id) {
    showPatientForm(id);
}

function editDoctor(id) {
    showDoctorForm(id);
}

function editAppointment(id) {
    showAppointmentForm(id);
}

function editMedicine(id) {
    showMedicineForm(id);
}

function editInvoice(id) {
    showInvoiceForm(id);
}

// ==================== PATIENTS CRUD ====================
async function loadPatients() {
    try {
        showLoading('patients-list');
        const patients = await apiCall('patients.php');
        
        const table = document.getElementById('patients-list');
        if (table) {
            table.innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>No. RM</th>
                            <th>Nama</th>
                            <th>Jenis Kelamin</th>
                            <th>Tanggal Lahir</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${patients.map(patient => `
                            <tr>
                                <td>${patient.medical_record_number || 'N/A'}</td>
                                <td>${patient.full_name || 'N/A'}</td>
                                <td>${patient.gender === 'M' ? 'Laki-laki' : 'Perempuan'}</td>
                                <td>${formatDate(patient.birth_date)}</td>
                                <td>${patient.phone || '-'}</td>
                                <td>${patient.address ? patient.address.substring(0, 30) + '...' : '-'}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit btn-sm" onclick="editPatient(${patient.id})">Edit</button>
                                        <button class="btn-danger btn-sm" onclick="deletePatient(${patient.id})">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (error) {
        showError('patients-list', 'Error memuat data pasien: ' + error.message);
    }
}

function showPatientForm(patientId = null) {
    const form = document.getElementById('patient-form');
    const title = document.getElementById('patient-form-title');
    
    if (form && title) {
        if (patientId) {
            title.textContent = 'Edit Pasien';
            currentEditingId = patientId;
            loadPatientData(patientId);
        } else {
            title.textContent = 'Tambah Pasien Baru';
            const patientForm = document.getElementById('patientForm');
            if (patientForm) patientForm.reset();
            currentEditingId = null;
        }
        
        form.style.display = 'block';
    }
}

function hidePatientForm() {
    const form = document.getElementById('patient-form');
    if (form) form.style.display = 'none';
}

async function loadPatientData(id) {
    try {
        const patients = await apiCall('patients.php');
        const patient = patients.find(p => p.id == id);
        
        if (patient) {
            const fields = [
                'patient_id', 'medical_record_number', 'full_name', 'gender', 
                'birth_date', 'phone', 'email', 'address', 'blood_type', 
                'allergies', 'emergency_contact'
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    element.value = patient[field] || '';
                }
            });
        }
    } catch (error) {
        showAlert('Error memuat data pasien: ' + error.message, 'error');
    }
}

// Event listener untuk patient form
const patientForm = document.getElementById('patientForm');
if (patientForm) {
    patientForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        try {
            const url = currentEditingId ? `patients.php?id=${currentEditingId}` : 'patients.php';
            const method = currentEditingId ? 'PUT' : 'POST';
            
            const result = await apiCall(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (result.success) {
                showAlert(`Pasien berhasil ${currentEditingId ? 'diupdate' : 'ditambahkan'}`, 'success');
                hidePatientForm();
                loadPatients();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menyimpan data: ' + error.message, 'error');
        }
    });
}

async function deletePatient(id) {
    if (confirm('Apakah Anda yakin ingin menghapus pasien ini?')) {
        try {
            const result = await apiCall(`patients.php?id=${id}`, { 
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            if (result.success) {
                showAlert('Pasien berhasil dihapus', 'success');
                loadPatients();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menghapus pasien: ' + error.message, 'error');
        }
    }
}

// ==================== DOCTORS CRUD ====================
async function loadDoctors() {
    try {
        showLoading('doctors-list');
        const doctors = await apiCall('doctors.php');
        
        const table = document.getElementById('doctors-list');
        if (table) {
            table.innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Spesialisasi</th>
                            <th>Telepon</th>
                            <th>No. Lisensi</th>
                            <th>Pengalaman</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${doctors.map(doctor => `
                            <tr>
                                <td>${doctor.full_name || 'N/A'}</td>
                                <td>${doctor.specialization || 'N/A'}</td>
                                <td>${doctor.phone || '-'}</td>
                                <td>${doctor.license_number || '-'}</td>
                                <td>${doctor.experience_years || 0} tahun</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit btn-sm" onclick="editDoctor(${doctor.id})">Edit</button>
                                        <button class="btn-danger btn-sm" onclick="deleteDoctor(${doctor.id})">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (error) {
        showError('doctors-list', 'Error memuat data dokter: ' + error.message);
    }
}

function showDoctorForm(doctorId = null) {
    const form = document.getElementById('doctor-form');
    const title = document.getElementById('doctor-form-title');
    
    if (form && title) {
        if (doctorId) {
            title.textContent = 'Edit Dokter';
            currentEditingId = doctorId;
            loadDoctorData(doctorId);
        } else {
            title.textContent = 'Tambah Dokter Baru';
            const doctorForm = document.getElementById('doctorForm');
            if (doctorForm) doctorForm.reset();
            currentEditingId = null;
        }
        
        form.style.display = 'block';
    }
}

function hideDoctorForm() {
    const form = document.getElementById('doctor-form');
    if (form) form.style.display = 'none';
}

async function loadDoctorData(id) {
    try {
        const doctors = await apiCall('doctors.php');
        const doctor = doctors.find(d => d.id == id);
        
        if (doctor) {
            const fields = [
                'doctor_id', 'doctor_full_name', 'specialization', 'doctor_phone',
                'doctor_email', 'license_number', 'experience_years', 'education', 
                'schedule', 'notes'
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    element.value = doctor[field.replace('doctor_', '')] || '';
                }
            });
        }
    } catch (error) {
        showAlert('Error memuat data dokter: ' + error.message, 'error');
    }
}

// Event listener untuk doctor form
const doctorForm = document.getElementById('doctorForm');
if (doctorForm) {
    doctorForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        try {
            const url = currentEditingId ? `doctors.php?id=${currentEditingId}` : 'doctors.php';
            const method = currentEditingId ? 'PUT' : 'POST';
            
            const result = await apiCall(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (result.success) {
                showAlert(`Dokter berhasil ${currentEditingId ? 'diupdate' : 'ditambahkan'}`, 'success');
                hideDoctorForm();
                loadDoctors();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menyimpan data: ' + error.message, 'error');
        }
    });
}

async function deleteDoctor(id) {
    if (confirm('Apakah Anda yakin ingin menghapus dokter ini?')) {
        try {
            const result = await apiCall(`doctors.php?id=${id}`, { 
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            if (result.success) {
                showAlert('Dokter berhasil dihapus', 'success');
                loadDoctors();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menghapus dokter: ' + error.message, 'error');
        }
    }
}

// ==================== APPOINTMENTS CRUD ====================
async function loadAppointments() {
    try {
        showLoading('appointments-list');
        const appointments = await apiCall('appointments.php');
        
        const table = document.getElementById('appointments-list');
        if (table) {
            table.innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Ruangan</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${appointments.map(apt => `
                            <tr>
                                <td>${apt.id}</td>
                                <td>${apt.patient_name || 'N/A'}</td>
                                <td>${apt.doctor_name || 'N/A'}</td>
                                <td>${apt.room_name || 'N/A'}</td>
                                <td>${formatDateTime(apt.scheduled_at)}</td>
                                <td><span class="status-badge status-${apt.status}">${getStatusText(apt.status)}</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit btn-sm" onclick="editAppointment(${apt.id})">Edit</button>
                                        <button class="btn-danger btn-sm" onclick="deleteAppointment(${apt.id})">Hapus</button>
                                        ${apt.status === 'scheduled' ? 
                                            `<button class="btn-success btn-sm" onclick="updateAppointmentStatus(${apt.id}, 'completed')">Selesai</button>` : 
                                            ''
                                        }
                                        ${apt.status === 'completed' ? 
                                            `<button class="btn-info btn-sm" onclick="createInvoiceFromAppointment(${apt.id})">Buat Invoice</button>` : 
                                            ''
                                        }
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (error) {
        showError('appointments-list', 'Error memuat data janji temu: ' + error.message);
    }
}

function showAppointmentForm(appointmentId = null) {
    const form = document.getElementById('appointment-form');
    const title = document.getElementById('appointment-form-title');
    
    if (form && title) {
        if (appointmentId) {
            title.textContent = 'Edit Janji Temu';
            currentEditingId = appointmentId;
            loadAppointmentData(appointmentId);
        } else {
            title.textContent = 'Tambah Janji Baru';
            const appointmentForm = document.getElementById('appointmentForm');
            if (appointmentForm) appointmentForm.reset();
            currentEditingId = null;
        }
        
        form.style.display = 'block';
    }
}

function hideAppointmentForm() {
    const form = document.getElementById('appointment-form');
    if (form) form.style.display = 'none';
}

async function loadAppointmentData(id) {
    try {
        const appointments = await apiCall('appointments.php');
        const appointment = appointments.find(a => a.id == id);
        
        if (appointment) {
            const fields = [
                'appointment_id', 'patient_id', 'doctor_id', 'room_id',
                'scheduled_at', 'appointment_type', 'status', 'complaint', 'appointment_notes'
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    let value = appointment[field.replace('appointment_', '')] || '';
                    if (field === 'scheduled_at' && value) {
                        value = value.replace(' ', 'T');
                    }
                    element.value = value;
                }
            });
        }
    } catch (error) {
        showAlert('Error memuat data janji temu: ' + error.message, 'error');
    }
}

// Event listener untuk appointment form
const appointmentForm = document.getElementById('appointmentForm');
if (appointmentForm) {
    appointmentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log('📝 Appointment form submitted');
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        // Convert empty strings to empty strings (bukan null)
        if (!data.complaint) data.complaint = '';
        if (!data.notes) data.notes = '';
        
        // Format scheduled_at untuk database
        if (data.scheduled_at) {
            // Convert from datetime-local format to MySQL datetime format
            data.scheduled_at = data.scheduled_at.replace('T', ' ') + ':00';
        }
        
        // Pastikan ID ada untuk update
        if (currentEditingId) {
            data.id = currentEditingId;
        }
        
        console.log('📦 Data to send:', data);
        
        try {
            const url = currentEditingId ? `appointments.php?id=${currentEditingId}` : 'appointments.php';
            const method = currentEditingId ? 'PUT' : 'POST';
            
            console.log(`🚀 Sending ${method} request to ${url}`);
            
            const result = await apiCall(url, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            console.log('✅ Server response:', result);
            
            if (result.success) {
                showAlert(`Janji temu berhasil ${currentEditingId ? 'diupdate' : 'ditambahkan'}`, 'success');
                hideAppointmentForm();
                loadAppointments();
                loadDashboard();
            } else {
                showAlert('Error dari server: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('❌ Error saving appointment:', error);
            showAlert('Error menyimpan data: ' + error.message, 'error');
        }
    });
}

async function deleteAppointment(id) {
    if (confirm('Apakah Anda yakin ingin menghapus janji temu ini?')) {
        try {
            const result = await apiCall(`appointments.php?id=${id}`, { 
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            if (result.success) {
                showAlert('Janji temu berhasil dihapus', 'success');
                loadAppointments();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('❌ Error deleting appointment:', error);
            showAlert('Error menghapus janji temu: ' + error.message, 'error');
        }
    }
}

async function updateAppointmentStatus(id, status) {
    try {
        const result = await apiCall(`appointments.php?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                status: status,
                id: id // Tambahkan ID di body juga untuk backup
            })
        });
        
        if (result.success) {
            showAlert('Status janji temu berhasil diupdate', 'success');
            loadAppointments();
            loadDashboard();
        } else {
            showAlert('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ Error updating appointment status:', error);
        showAlert('Error mengupdate status: ' + error.message, 'error');
    }
}

async function createInvoiceFromAppointment(appointmentId) {
    try {
        const appointments = await apiCall('appointments.php');
        const appointment = appointments.find(a => a.id == appointmentId);
        
        if (appointment) {
            const invoiceAppointmentId = document.getElementById('invoice_appointment_id');
            const invoiceTotalAmount = document.getElementById('invoice_total_amount');
            
            if (invoiceAppointmentId) invoiceAppointmentId.value = appointmentId;
            if (invoiceTotalAmount) {
                const defaultAmounts = {
                    'consultation': 150000,
                    'treatment': 300000,
                    'surgery': 1000000,
                    'checkup': 200000
                };
                const defaultAmount = defaultAmounts[appointment.type] || 150000;
                invoiceTotalAmount.value = defaultAmount;
            }
            showInvoiceForm();
        }
    } catch (error) {
        showAlert('Error memuat data janji temu: ' + error.message, 'error');
    }
}

// ==================== MEDICINES CRUD ====================
async function loadMedicines() {
    try {
        showLoading('medicines-list');
        const medicines = await apiCall('medicines.php');
        
        const table = document.getElementById('medicines-list');
        if (table) {
            table.innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Stok Min</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${medicines.map(medicine => `
                            <tr>
                                <td>${medicine.code || 'N/A'}</td>
                                <td>${medicine.name || 'N/A'}</td>
                                <td>${medicine.unit || 'N/A'}</td>
                                <td>${formatCurrency(medicine.price)}</td>
                                <td>${medicine.stock || 0}</td>
                                <td>${medicine.min_stock || 0}</td>
                                <td>
                                    <span class="status-badge ${medicine.stock <= medicine.min_stock ? 'stock-low' : 'stock-adequate'}">
                                        ${medicine.stock <= medicine.min_stock ? 'Stok Rendah' : 'Aman'}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit btn-sm" onclick="editMedicine(${medicine.id})">Edit</button>
                                        <button class="btn-danger btn-sm" onclick="deleteMedicine(${medicine.id})">Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (error) {
        showError('medicines-list', 'Error memuat data obat: ' + error.message);
    }
}

function showMedicineForm(medicineId = null) {
    const form = document.getElementById('medicine-form');
    const title = document.getElementById('medicine-form-title');
    
    if (form && title) {
        if (medicineId) {
            title.textContent = 'Edit Obat';
            currentEditingId = medicineId;
            loadMedicineData(medicineId);
        } else {
            title.textContent = 'Tambah Obat Baru';
            const medicineForm = document.getElementById('medicineForm');
            if (medicineForm) medicineForm.reset();
            currentEditingId = null;
        }
        
        form.style.display = 'block';
    }
}

function hideMedicineForm() {
    const form = document.getElementById('medicine-form');
    if (form) form.style.display = 'none';
}

async function loadMedicineData(id) {
    try {
        const medicines = await apiCall('medicines.php');
        const medicine = medicines.find(m => m.id == id);
        
        if (medicine) {
            const fields = [
                'medicine_id', 'code', 'medicine_name', 'generic_name', 'category',
                'unit', 'price', 'stock', 'min_stock', 'supplier', 'expiry_date', 'description'
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    element.value = medicine[field.replace('medicine_', '')] || '';
                }
            });
        }
    } catch (error) {
        showAlert('Error memuat data obat: ' + error.message, 'error');
    }
}

// Event listener untuk medicine form
const medicineForm = document.getElementById('medicineForm');
if (medicineForm) {
    medicineForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        try {
            const url = currentEditingId ? `medicines.php?id=${currentEditingId}` : 'medicines.php';
            const method = currentEditingId ? 'PUT' : 'POST';
            
            const result = await apiCall(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (result.success) {
                showAlert(`Obat berhasil ${currentEditingId ? 'diupdate' : 'ditambahkan'}`, 'success');
                hideMedicineForm();
                loadMedicines();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menyimpan data: ' + error.message, 'error');
        }
    });
}

async function deleteMedicine(id) {
    if (confirm('Apakah Anda yakin ingin menghapus obat ini?')) {
        try {
            const result = await apiCall(`medicines.php?id=${id}`, { 
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            if (result.success) {
                showAlert('Obat berhasil dihapus', 'success');
                loadMedicines();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menghapus obat: ' + error.message, 'error');
        }
    }
}

// ==================== INVOICES CRUD ====================
async function loadInvoices() {
    try {
        showLoading('invoices-list');
        const invoices = await apiCall('invoices.php');
        
        const table = document.getElementById('invoices-list');
        if (table) {
            table.innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Total</th>
                            <th>Dibayar</th>
                            <th>Sisa</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${invoices.map(invoice => {
                            const remaining = invoice.total_amount - invoice.paid_amount;
                            return `
                            <tr>
                                <td>${invoice.invoice_number || 'N/A'}</td>
                                <td>${invoice.patient_name || 'N/A'}</td>
                                <td>${invoice.doctor_name || 'N/A'}</td>
                                <td>${formatCurrency(invoice.total_amount)}</td>
                                <td>${formatCurrency(invoice.paid_amount)}</td>
                                <td>${formatCurrency(remaining)}</td>
                                <td><span class="status-badge status-${invoice.status}">${getInvoiceStatusText(invoice.status)}</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-success btn-sm" onclick="showPaymentModal(${invoice.id})">Bayar</button>
                                        <button class="btn-edit btn-sm" onclick="editInvoice(${invoice.id})">Edit</button>
                                        ${invoice.status === 'unpaid' || invoice.status === 'partial' ? 
                                            `<button class="btn-danger btn-sm" onclick="deleteInvoice(${invoice.id})">Hapus</button>` : 
                                            ''
                                        }
                                    </div>
                                </td>
                            </tr>
                        `}).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (error) {
        showError('invoices-list', 'Error memuat data invoice: ' + error.message);
    }
}

function showInvoiceForm(invoiceId = null) {
    const form = document.getElementById('invoice-form');
    const title = document.getElementById('invoice-form-title');
    
    if (form && title) {
        if (invoiceId) {
            title.textContent = 'Edit Invoice';
            currentEditingId = invoiceId;
            loadInvoiceData(invoiceId);
        } else {
            title.textContent = 'Buat Invoice Baru';
            const invoiceForm = document.getElementById('invoiceForm');
            if (invoiceForm) invoiceForm.reset();
            currentEditingId = null;
        }
        
        form.style.display = 'block';
    }
}

function hideInvoiceForm() {
    const form = document.getElementById('invoice-form');
    if (form) form.style.display = 'none';
}

async function loadInvoiceData(id) {
    try {
        const invoices = await apiCall('invoices.php');
        const invoice = invoices.find(i => i.id == id);
        
        if (invoice) {
            const fields = [
                'invoice_id', 'invoice_appointment_id', 'invoice_total_amount', 'payment_method'
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    element.value = invoice[field.replace('invoice_', '')] || '';
                }
            });
        }
    } catch (error) {
        showAlert('Error memuat data invoice: ' + error.message, 'error');
    }
}

// Event listener untuk invoice form
const invoiceForm = document.getElementById('invoiceForm');
if (invoiceForm) {
    invoiceForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        try {
            let result;
            if (currentEditingId) {
                result = await apiCall(`invoices.php?id=${currentEditingId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
            } else {
                result = await apiCall('invoices.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
            }
            
            if (result.success) {
                showAlert(`Invoice berhasil ${currentEditingId ? 'diupdate' : 'dibuat'}: ${result.invoice_number || ''}`, 'success');
                hideInvoiceForm();
                loadInvoices();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menyimpan invoice: ' + error.message, 'error');
        }
    });
}

function showPaymentModal(invoiceId) {
    currentEditingId = invoiceId;
    const modal = document.getElementById('paymentModal');
    if (modal) {
        modal.style.display = 'block';
        loadInvoicePaymentData(invoiceId);
    }
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    if (modal) modal.style.display = 'none';
}

async function loadInvoicePaymentData(id) {
    try {
        const invoices = await apiCall('invoices.php');
        const invoice = invoices.find(i => i.id == id);
        
        if (invoice) {
            const fields = [
                'payment_invoice_id', 'payment_invoice_number', 
                'payment_total', 'payment_paid', 'payment_payment_method'
            ];
            
            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    if (field === 'payment_total' || field === 'payment_paid') {
                        element.value = formatCurrency(invoice[field.replace('payment_', '')]);
                    } else {
                        element.value = invoice[field.replace('payment_', '')] || '';
                    }
                }
            });
            
            const paymentAmount = document.getElementById('payment_amount');
            if (paymentAmount) {
                const remaining = invoice.total_amount - invoice.paid_amount;
                paymentAmount.value = remaining > 0 ? remaining.toFixed(2) : '0';
                paymentAmount.max = remaining;
            }
        }
    } catch (error) {
        showAlert('Error memuat data invoice: ' + error.message, 'error');
    }
}

// Event listener untuk payment form
const paymentForm = document.getElementById('paymentForm');
if (paymentForm) {
    paymentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const amount = parseFloat(document.getElementById('payment_amount').value);
        const invoiceId = document.getElementById('payment_invoice_id').value;
        const paymentMethod = document.getElementById('payment_payment_method').value;
        
        if (amount <= 0) {
            showAlert('Jumlah pembayaran harus lebih dari 0', 'error');
            return;
        }
        
        try {
            const result = await apiCall(`invoices.php?id=${invoiceId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    paid_amount: amount,
                    payment_method: paymentMethod
                })
            });
            
            if (result.success) {
                showAlert('Pembayaran berhasil diproses', 'success');
                closePaymentModal();
                loadInvoices();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error memproses pembayaran: ' + error.message, 'error');
        }
    });
}

async function deleteInvoice(id) {
    if (confirm('Apakah Anda yakin ingin menghapus invoice ini?')) {
        try {
            const result = await apiCall(`invoices.php?id=${id}`, { 
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            if (result.success) {
                showAlert('Invoice berhasil dihapus', 'success');
                loadInvoices();
                loadDashboard();
            } else {
                showAlert('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showAlert('Error menghapus invoice: ' + error.message, 'error');
        }
    }
}

// ==================== DEBUG & TESTING FUNCTIONS ====================
// Fungsi untuk test API endpoints
window.testAPI = async function() {
    console.log('🧪 Testing API endpoints...');
    
    const endpoints = [
        'test_api.php',
        'patients.php',
        'doctors.php', 
        'appointments.php',
        'medicines.php',
        'invoices.php',
        'rooms.php',
        'dummy_data.php'
    ];
    
    for (const endpoint of endpoints) {
        try {
            console.log(`🔍 Testing ${endpoint}...`);
            const response = await fetch(API_BASE + endpoint);
            const data = await response.json();
            console.log(`✅ ${endpoint}:`, data.length || '0 items', data);
        } catch (error) {
            console.error(`❌ ${endpoint}:`, error);
        }
    }
};

// Fungsi untuk manual dashboard debug
window.debugDashboard = function() {
    console.log('🔧 Manual dashboard debug');
    loadDashboard();
};

// Debug function untuk testing appointment CRUD
window.testAppointmentCRUD = async function() {
    console.log('🧪 Testing Appointment CRUD...');
    
    try {
        // Test GET
        console.log('1. Testing GET...');
        const appointments = await apiCall('appointments.php');
        console.log('✅ GET success:', appointments.length, 'appointments');
        
        if (appointments.length > 0) {
            const firstAppointment = appointments[0];
            
            // Test PUT
            console.log('2. Testing PUT...');
            const updateResult = await apiCall(`appointments.php?id=${firstAppointment.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    status: 'completed',
                    id: firstAppointment.id 
                })
            });
            console.log('✅ PUT success:', updateResult);
            
            // Test DELETE (hanya jika bukan completed)
            if (firstAppointment.status !== 'completed') {
                console.log('3. Testing DELETE...');
                const deleteResult = await apiCall(`appointments.php?id=${firstAppointment.id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: firstAppointment.id })
                });
                console.log('✅ DELETE success:', deleteResult);
            }
        }
        
        // Test POST
        console.log('4. Testing POST...');
        const postResult = await apiCall('appointments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                patient_id: 1,
                doctor_id: 1, 
                room_id: 1,
                scheduled_at: '2024-12-11 14:00:00',
                status: 'scheduled',
                type: 'consultation',
                complaint: 'Test from debug function'
            })
        });
        console.log('✅ POST success:', postResult);
        
        showAlert('CRUD testing completed! Check console for details.', 'success');
        
    } catch (error) {
        console.error('❌ CRUD test failed:', error);
        showAlert('CRUD test failed: ' + error.message, 'error');
    }
};

// Debug function untuk testing form
window.debugAppointmentForm = function() {
    console.log('🔧 Debug Appointment Form');
    const formData = new FormData(document.getElementById('appointmentForm'));
    const data = Object.fromEntries(formData);
    console.log('Form data:', data);
    
    // Test API call langsung
    apiCall('appointments.php', {
        method: 'POST',
        body: JSON.stringify({
            patient_id: 1,
            doctor_id: 1, 
            room_id: 1,
            scheduled_at: '2024-12-11 10:00:00',
            status: 'scheduled',
            type: 'consultation',
            complaint: 'Test from debug function'
        })
    }).then(result => {
        console.log('✅ Debug API call success:', result);
        showAlert('Debug: API call berhasil!', 'success');
    }).catch(error => {
        console.error('❌ Debug API call failed:', error);
        showAlert('Debug: ' + error.message, 'error');
    });
};

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM fully loaded and parsed');
    console.log('🚀 Sistem Manajemen Klinik initialized');
    
    // Load dashboard
    loadDashboard();
    
    // Untuk development, test API endpoints
    setTimeout(() => {
        console.log('🧪 Running initial API test...');
        testAPI();
    }, 1000);
});