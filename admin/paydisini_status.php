<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/paydisini.php';

// Cek admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Test PayDisini API status
$paydisini = new PayDisini();
$testResult = null;
$apiStatus = 'unknown';

try {
    // Test API call
    $response = $paydisini->makeRequest('qris', [
        'key' => 'ff79be802563e5dc1311c227a72d17c1',
        'request' => 'new',
        'merchant_id' => '3246',
        'amount' => 1000,
        'note' => 'Test connection',
        'type_fee' => '1',
        'payment_method' => 'qris',
        'order_id' => 'TEST_' . time(),
        'valid_time' => 3600
    ]);
    
    if (isset($response['success'])) {
        if ($response['success'] === true) {
            $apiStatus = 'active';
            $testResult = 'API PayDisini berfungsi normal';
        } else {
            $apiStatus = 'error';
            $testResult = 'Error: ' . ($response['msg'] ?? 'Unknown error');
        }
    } else {
        $apiStatus = 'invalid_response';
        $testResult = 'Response tidak valid dari API';
    }
} catch (Exception $e) {
    $apiStatus = 'connection_error';
    $testResult = 'Connection error: ' . $e->getMessage();
}

include 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status PayDisini API</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Konfigurasi API</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Merchant ID</td>
                                    <td><code>3246</code></td>
                                </tr>
                                <tr>
                                    <td>API Key</td>
                                    <td><code>ff79***************************17c1</code></td>
                                </tr>
                                <tr>
                                    <td>Endpoint</td>
                                    <td><code>https://paydisini.co.id/api/</code></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Status Koneksi</h6>
                            <div class="alert <?= $apiStatus === 'active' ? 'alert-success' : 'alert-danger' ?>">
                                <?php if ($apiStatus === 'active'): ?>
                                    <i class="fas fa-check-circle me-2"></i>API Aktif
                                <?php elseif ($apiStatus === 'error'): ?>
                                    <i class="fas fa-exclamation-triangle me-2"></i>API Error
                                <?php else: ?>
                                    <i class="fas fa-times-circle me-2"></i>Tidak Terhubung
                                <?php endif; ?>
                            </div>
                            <p><strong>Detail:</strong> <?= htmlspecialchars($testResult) ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Troubleshooting</h6>
                    <div class="alert alert-info">
                        <h6>Jika API mengalami masalah:</h6>
                        <ol class="mb-0">
                            <li>Pastikan akun PayDisini tidak ditangguhkan</li>
                            <li>Cek saldo deposit di dashboard PayDisini</li>
                            <li>Verifikasi API key dan merchant ID</li>
                            <li>Hubungi support PayDisini jika masalah berlanjut</li>
                        </ol>
                    </div>
                    
                    <div class="mt-3">
                        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
                        <button onclick="location.reload()" class="btn btn-primary">Test Ulang</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>