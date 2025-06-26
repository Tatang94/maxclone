<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in as merchant
if (isset($_SESSION['merchant_id'])) {
    header('Location: merchant_dashboard.php');
    exit();
}

$pageTitle = 'Daftar Merchant - RideMax';
include 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="bg-primary text-white p-4 mb-4">
        <div class="d-flex align-items-center mb-3">
            <a href="index.php" class="btn btn-light btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h4 class="mb-0">Daftar Sebagai Merchant</h4>
        </div>
        <p class="mb-0 opacity-75">Bergabung dan mulai berjualan dengan RideMax</p>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="merchantRegisterForm" method="POST" action="process/merchant_register_process.php">
                            <!-- Business Information -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-store"></i> Informasi Bisnis
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="business_name" class="form-label">Nama Bisnis *</label>
                                    <input type="text" class="form-control form-control-lg" id="business_name" name="business_name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="business_category" class="form-label">Kategori Bisnis *</label>
                                    <select class="form-select form-select-lg" id="business_category" name="business_category" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Makanan & Minuman">Makanan & Minuman</option>
                                        <option value="Restoran">Restoran</option>
                                        <option value="Kafe">Kafe</option>
                                        <option value="Warung">Warung</option>
                                        <option value="Toko Kelontong">Toko Kelontong</option>
                                        <option value="Supermarket">Supermarket</option>
                                        <option value="Farmasi">Farmasi</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="business_phone" class="form-label">Nomor Telepon Bisnis *</label>
                                    <input type="tel" class="form-control form-control-lg" id="business_phone" name="business_phone" required>
                                </div>

                                <div class="mb-3">
                                    <label for="business_email" class="form-label">Email Bisnis</label>
                                    <input type="email" class="form-control form-control-lg" id="business_email" name="business_email">
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-map-marker-alt"></i> Alamat Bisnis
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="province" class="form-label">Provinsi *</label>
                                        <select class="form-select form-select-lg" id="province" name="province" required>
                                            <option value="">Pilih Provinsi</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">Kota/Kabupaten *</label>
                                        <select class="form-select form-select-lg" id="city" name="city" required disabled>
                                            <option value="">Pilih Kota/Kabupaten</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="district" class="form-label">Kecamatan *</label>
                                        <select class="form-select form-select-lg" id="district" name="district" required disabled>
                                            <option value="">Pilih Kecamatan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="village" class="form-label">Desa/Kelurahan *</label>
                                        <select class="form-select form-select-lg" id="village" name="village" required disabled>
                                            <option value="">Pilih Desa/Kelurahan</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="detailed_address" class="form-label">Alamat Detail *</label>
                                    <textarea class="form-control form-control-lg" id="detailed_address" name="detailed_address" rows="3" placeholder="Jalan, nomor rumah, patokan..." required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Kode Pos</label>
                                    <input type="text" class="form-control form-control-lg" id="postal_code" name="postal_code" maxlength="5">
                                </div>
                            </div>

                            <!-- Owner Information -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-user"></i> Informasi Pemilik
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="owner_name" class="form-label">Nama Lengkap Pemilik *</label>
                                    <input type="text" class="form-control form-control-lg" id="owner_name" name="owner_name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="owner_phone" class="form-label">Nomor Telepon Pemilik *</label>
                                    <input type="tel" class="form-control form-control-lg" id="owner_phone" name="owner_phone" required>
                                </div>

                                <div class="mb-3">
                                    <label for="owner_email" class="form-label">Email Pemilik *</label>
                                    <input type="email" class="form-control form-control-lg" id="owner_email" name="owner_email" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" required minlength="6">
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                                    <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                                    <label class="form-check-label" for="agree_terms">
                                        Saya setuju dengan <a href="#" class="text-primary">Syarat dan Ketentuan</a> dan <a href="#" class="text-primary">Kebijakan Privasi</a> RideMax
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus"></i> Daftar Sebagai Merchant
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Sudah punya akun merchant? <a href="merchant_login.php" class="text-primary">Login di sini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Indonesian regions data
const indonesiaRegions = {
    provinces: [
        { id: '11', name: 'Aceh' },
        { id: '12', name: 'Sumatera Utara' },
        { id: '13', name: 'Sumatera Barat' },
        { id: '14', name: 'Riau' },
        { id: '15', name: 'Jambi' },
        { id: '16', name: 'Sumatera Selatan' },
        { id: '17', name: 'Bengkulu' },
        { id: '18', name: 'Lampung' },
        { id: '19', name: 'Kepulauan Bangka Belitung' },
        { id: '21', name: 'Kepulauan Riau' },
        { id: '31', name: 'DKI Jakarta' },
        { id: '32', name: 'Jawa Barat' },
        { id: '33', name: 'Jawa Tengah' },
        { id: '34', name: 'DI Yogyakarta' },
        { id: '35', name: 'Jawa Timur' },
        { id: '36', name: 'Banten' },
        { id: '51', name: 'Bali' },
        { id: '52', name: 'Nusa Tenggara Barat' },
        { id: '53', name: 'Nusa Tenggara Timur' },
        { id: '61', name: 'Kalimantan Barat' },
        { id: '62', name: 'Kalimantan Tengah' },
        { id: '63', name: 'Kalimantan Selatan' },
        { id: '64', name: 'Kalimantan Timur' },
        { id: '65', name: 'Kalimantan Utara' },
        { id: '71', name: 'Sulawesi Utara' },
        { id: '72', name: 'Sulawesi Tengah' },
        { id: '73', name: 'Sulawesi Selatan' },
        { id: '74', name: 'Sulawesi Tenggara' },
        { id: '75', name: 'Gorontalo' },
        { id: '76', name: 'Sulawesi Barat' },
        { id: '81', name: 'Maluku' },
        { id: '82', name: 'Maluku Utara' },
        { id: '91', name: 'Papua Barat' },
        { id: '94', name: 'Papua' }
    ],
    cities: {
        '32': [ // Jawa Barat
            { id: '3201', name: 'Kabupaten Bogor' },
            { id: '3202', name: 'Kabupaten Sukabumi' },
            { id: '3203', name: 'Kabupaten Cianjur' },
            { id: '3204', name: 'Kabupaten Bandung' },
            { id: '3205', name: 'Kabupaten Garut' },
            { id: '3206', name: 'Kabupaten Tasikmalaya' },
            { id: '3207', name: 'Kabupaten Ciamis' },
            { id: '3208', name: 'Kabupaten Kuningan' },
            { id: '3209', name: 'Kabupaten Cirebon' },
            { id: '3210', name: 'Kabupaten Majalengka' },
            { id: '3211', name: 'Kabupaten Sumedang' },
            { id: '3212', name: 'Kabupaten Indramayu' },
            { id: '3213', name: 'Kabupaten Subang' },
            { id: '3214', name: 'Kabupaten Purwakarta' },
            { id: '3215', name: 'Kabupaten Karawang' },
            { id: '3216', name: 'Kabupaten Bekasi' },
            { id: '3217', name: 'Kabupaten Bandung Barat' },
            { id: '3218', name: 'Kabupaten Pangandaran' },
            { id: '3271', name: 'Kota Bogor' },
            { id: '3272', name: 'Kota Sukabumi' },
            { id: '3273', name: 'Kota Bandung' },
            { id: '3274', name: 'Kota Cirebon' },
            { id: '3275', name: 'Kota Bekasi' },
            { id: '3276', name: 'Kota Depok' },
            { id: '3277', name: 'Kota Cimahi' },
            { id: '3278', name: 'Kota Tasikmalaya' },
            { id: '3279', name: 'Kota Banjar' }
        ],
        '31': [ // DKI Jakarta
            { id: '3171', name: 'Kepulauan Seribu' },
            { id: '3172', name: 'Jakarta Selatan' },
            { id: '3173', name: 'Jakarta Timur' },
            { id: '3174', name: 'Jakarta Pusat' },
            { id: '3175', name: 'Jakarta Barat' },
            { id: '3176', name: 'Jakarta Utara' }
        ],
        '33': [ // Jawa Tengah
            { id: '3301', name: 'Kabupaten Cilacap' },
            { id: '3302', name: 'Kabupaten Banyumas' },
            { id: '3303', name: 'Kabupaten Purbalingga' },
            { id: '3304', name: 'Kabupaten Banjarnegara' },
            { id: '3305', name: 'Kabupaten Kebumen' },
            { id: '3306', name: 'Kabupaten Purworejo' },
            { id: '3307', name: 'Kabupaten Wonosobo' },
            { id: '3308', name: 'Kabupaten Magelang' },
            { id: '3309', name: 'Kabupaten Boyolali' },
            { id: '3310', name: 'Kabupaten Klaten' },
            { id: '3311', name: 'Kabupaten Sukoharjo' },
            { id: '3312', name: 'Kabupaten Wonogiri' },
            { id: '3313', name: 'Kabupaten Karanganyar' },
            { id: '3314', name: 'Kabupaten Sragen' },
            { id: '3315', name: 'Kabupaten Grobogan' },
            { id: '3316', name: 'Kabupaten Blora' },
            { id: '3317', name: 'Kabupaten Rembang' },
            { id: '3318', name: 'Kabupaten Pati' },
            { id: '3319', name: 'Kabupaten Kudus' },
            { id: '3320', name: 'Kabupaten Jepara' },
            { id: '3321', name: 'Kabupaten Demak' },
            { id: '3322', name: 'Kabupaten Semarang' },
            { id: '3323', name: 'Kabupaten Temanggung' },
            { id: '3324', name: 'Kabupaten Kendal' },
            { id: '3325', name: 'Kabupaten Batang' },
            { id: '3326', name: 'Kabupaten Pekalongan' },
            { id: '3327', name: 'Kabupaten Pemalang' },
            { id: '3328', name: 'Kabupaten Tegal' },
            { id: '3329', name: 'Kabupaten Brebes' },
            { id: '3371', name: 'Kota Magelang' },
            { id: '3372', name: 'Kota Surakarta' },
            { id: '3373', name: 'Kota Salatiga' },
            { id: '3374', name: 'Kota Semarang' },
            { id: '3375', name: 'Kota Pekalongan' },
            { id: '3376', name: 'Kota Tegal' }
        ]
    },
    districts: {
        '3273': [ // Kota Bandung
            { id: '327301', name: 'Sukasari' },
            { id: '327302', name: 'Sukajadi' },
            { id: '327303', name: 'Cidadap' },
            { id: '327304', name: 'Andir' },
            { id: '327305', name: 'Cicendo' },
            { id: '327306', name: 'Bandung Kulon' },
            { id: '327307', name: 'Babakan Ciparay' },
            { id: '327308', name: 'Bojongloa Kaler' },
            { id: '327309', name: 'Bojongloa Kidul' },
            { id: '327310', name: 'Astana Anyar' },
            { id: '327311', name: 'Regol' },
            { id: '327312', name: 'Lengkong' },
            { id: '327313', name: 'Bandung Kidul' },
            { id: '327314', name: 'Buahbatu' },
            { id: '327315', name: 'Rancasari' },
            { id: '327316', name: 'Gedebage' },
            { id: '327317', name: 'Cibiru' },
            { id: '327318', name: 'Ujung Berung' },
            { id: '327319', name: 'Cinambo' },
            { id: '327320', name: 'Arcamanik' },
            { id: '327321', name: 'Antapani' },
            { id: '327322', name: 'Mandalajati' },
            { id: '327323', name: 'Kiaracondong' },
            { id: '327324', name: 'Batununggal' },
            { id: '327325', name: 'Sumur Bandung' },
            { id: '327326', name: 'Coblong' },
            { id: '327327', name: 'Bandung Wetan' },
            { id: '327328', name: 'Cibeunying Kidul' },
            { id: '327329', name: 'Cibeunying Kaler' },
            { id: '327330', name: 'Panyileukan' }
        ]
    },
    villages: {
        '327301': [ // Sukasari
            { id: '32730101', name: 'Geger Kalong' },
            { id: '32730102', name: 'Isola' },
            { id: '32730103', name: 'Sarijadi' },
            { id: '32730104', name: 'Sukarasa' }
        ],
        '327302': [ // Sukajadi
            { id: '32730201', name: 'Cipedes' },
            { id: '32730202', name: 'Pasteur' },
            { id: '32730203', name: 'Sukabungah' },
            { id: '32730204', name: 'Sukagalih' },
            { id: '32730205', name: 'Sukawarna' }
        ]
    }
};

document.addEventListener('DOMContentLoaded', function() {
    loadProvinces();
    setupFormValidation();
});

function loadProvinces() {
    const provinceSelect = document.getElementById('province');
    
    indonesiaRegions.provinces.forEach(province => {
        const option = document.createElement('option');
        option.value = province.id;
        option.textContent = province.name;
        provinceSelect.appendChild(option);
    });
}

function loadCities(provinceId) {
    const citySelect = document.getElementById('city');
    const districtSelect = document.getElementById('district');
    const villageSelect = document.getElementById('village');
    
    // Reset dependent dropdowns
    citySelect.innerHTML = '<option value="">Pilih Kota/Kabupaten</option>';
    districtSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
    villageSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
    
    citySelect.disabled = true;
    districtSelect.disabled = true;
    villageSelect.disabled = true;
    
    if (provinceId && indonesiaRegions.cities[provinceId]) {
        indonesiaRegions.cities[provinceId].forEach(city => {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
        citySelect.disabled = false;
    }
}

function loadDistricts(cityId) {
    const districtSelect = document.getElementById('district');
    const villageSelect = document.getElementById('village');
    
    // Reset dependent dropdowns
    districtSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
    villageSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
    
    districtSelect.disabled = true;
    villageSelect.disabled = true;
    
    if (cityId && indonesiaRegions.districts[cityId]) {
        indonesiaRegions.districts[cityId].forEach(district => {
            const option = document.createElement('option');
            option.value = district.id;
            option.textContent = district.name;
            districtSelect.appendChild(option);
        });
        districtSelect.disabled = false;
    }
}

function loadVillages(districtId) {
    const villageSelect = document.getElementById('village');
    
    // Reset dropdown
    villageSelect.innerHTML = '<option value="">Pilih Desa/Kelurahan</option>';
    villageSelect.disabled = true;
    
    if (districtId && indonesiaRegions.villages[districtId]) {
        indonesiaRegions.villages[districtId].forEach(village => {
            const option = document.createElement('option');
            option.value = village.id;
            option.textContent = village.name;
            villageSelect.appendChild(option);
        });
        villageSelect.disabled = false;
    }
}

// Event listeners for cascading dropdowns
document.getElementById('province').addEventListener('change', function() {
    loadCities(this.value);
});

document.getElementById('city').addEventListener('change', function() {
    loadDistricts(this.value);
});

document.getElementById('district').addEventListener('change', function() {
    loadVillages(this.value);
});

function setupFormValidation() {
    const form = document.getElementById('merchantRegisterForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    confirmPassword.addEventListener('input', function() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak sama');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Password dan konfirmasi password tidak sama');
            return false;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>