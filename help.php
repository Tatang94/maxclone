<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$page_title = "Bantuan & Dukungan";
require_once 'includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header -->
    <div class="bg-primary text-white p-4 mb-4">
        <div class="d-flex align-items-center">
            <button onclick="history.back()" class="btn btn-link text-white p-0 me-3">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Bantuan & Dukungan</h5>
        </div>
    </div>

    <div class="px-3">
        <!-- FAQ Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>
                    Pertanyaan yang Sering Diajukan
                </h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq1">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                Bagaimana cara memesan perjalanan?
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Buka menu "Pesan Perjalanan"</li>
                                    <li>Masukkan lokasi penjemputan dan tujuan</li>
                                    <li>Pilih jenis kendaraan (Bike, Car, atau Delivery)</li>
                                    <li>Pilih metode pembayaran (Saldo Dompet atau Tunai)</li>
                                    <li>Konfirmasi pesanan dan tunggu driver</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                Bagaimana cara mengisi saldo dompet?
                            </button>
                        </h2>
                        <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Buka menu "Dompet" di navigasi</li>
                                    <li>Klik tombol "Isi Saldo"</li>
                                    <li>Pilih nominal atau masukkan jumlah custom</li>
                                    <li>Klik "Buat Kode QR"</li>
                                    <li>Scan QR code dengan aplikasi e-wallet atau mobile banking</li>
                                    <li>Saldo akan otomatis bertambah setelah pembayaran berhasil</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                Apakah data saya aman?
                            </button>
                        </h2>
                        <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Ya, keamanan data Anda adalah prioritas kami. Kami menggunakan:
                                <ul>
                                    <li>Enkripsi SSL untuk semua komunikasi</li>
                                    <li>Password yang di-hash dengan algoritma aman</li>
                                    <li>Perlindungan terhadap serangan XSS dan SQL injection</li>
                                    <li>Sistem authentication yang aman</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                                Bagaimana jika driver tidak datang?
                            </button>
                        </h2>
                        <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Jika driver tidak datang dalam waktu yang wajar:
                                <ol>
                                    <li>Hubungi driver melalui nomor yang tersedia</li>
                                    <li>Jika tidak ada respon, batalkan pesanan</li>
                                    <li>Hubungi customer service untuk bantuan</li>
                                    <li>Uang akan dikembalikan jika menggunakan saldo dompet</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq5">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5">
                                Bagaimana cara menjadi driver?
                            </button>
                        </h2>
                        <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Untuk menjadi driver RideMax:
                                <ol>
                                    <li>Hubungi customer service untuk pendaftaran</li>
                                    <li>Siapkan dokumen: KTP, SIM, STNK</li>
                                    <li>Ikuti proses verifikasi</li>
                                    <li>Dapatkan akses driver setelah disetujui</li>
                                    <li>Mulai menerima pesanan!</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-headset me-2"></i>
                    Hubungi Dukungan
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <a href="https://wa.me/6281234567890" class="btn btn-success w-100 d-flex align-items-center justify-content-center py-3" target="_blank">
                            <i class="fab fa-whatsapp me-2 fa-lg"></i>
                            <div>
                                <div class="fw-bold">WhatsApp</div>
                                <small>+62 812-3456-7890</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="tel:+6281234567890" class="btn btn-primary w-100 d-flex align-items-center justify-content-center py-3">
                            <i class="fas fa-phone me-2"></i>
                            <div>
                                <div class="fw-bold">Telepon</div>
                                <small>24 Jam</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="mailto:support@ridemax.com" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-3">
                            <i class="fas fa-envelope me-2"></i>
                            <div>
                                <div class="fw-bold">Email</div>
                                <small>Support</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Kontak Darurat
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-3">Dalam situasi darurat atau kecelakaan:</p>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="tel:112" class="btn btn-danger w-100">
                            <i class="fas fa-phone me-2"></i>
                            Polisi: 112
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="tel:118" class="btn btn-danger w-100">
                            <i class="fas fa-ambulance me-2"></i>
                            Ambulans: 118
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Info -->
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-car fa-3x text-primary"></i>
                </div>
                <h6 class="fw-bold">RideMax</h6>
                <p class="text-muted mb-2">Versi 1.0.0</p>
                <p class="small text-muted">
                    Aplikasi transportasi online terpercaya untuk perjalanan Anda.
                    Dikembangkan dengan teknologi modern dan keamanan terjamin.
                </p>
                <div class="mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm me-2">Syarat & Ketentuan</a>
                    <a href="#" class="btn btn-outline-primary btn-sm">Kebijakan Privasi</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});
</script>