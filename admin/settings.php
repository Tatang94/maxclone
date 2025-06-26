<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Auto-login admin
if (!isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@ridemax.com' AND user_type = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    if ($admin) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_email'] = $admin['email'];
        $_SESSION['user_type'] = $admin['user_type'];
        $_SESSION['is_driver'] = false;
        $_SESSION['login_time'] = time();
    }
}

$pageTitle = 'Pengaturan Sistem - Admin RideMax';
include '../includes/header.php';

// Get current settings
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $settings = [];
}
?>

<div class="container-fluid p-0">
    <!-- Admin Header -->
    <div class="admin-header bg-dark text-white p-3">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white p-0 me-3" onclick="location.href='dashboard.php'">
                <i class="fas fa-arrow-left fa-lg"></i>
            </button>
            <h5 class="mb-0">Pengaturan Sistem</h5>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="settings-nav bg-light p-3">
        <ul class="nav nav-pills" id="settingsTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button">
                    <i class="fas fa-cog me-1"></i> General
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="pricing-tab" data-bs-toggle="pill" data-bs-target="#pricing" type="button">
                    <i class="fas fa-dollar-sign me-1"></i> Pricing
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" type="button">
                    <i class="fas fa-bell me-1"></i> Notifications
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button">
                    <i class="fas fa-shield-alt me-1"></i> Security
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="maintenance-tab" data-bs-toggle="pill" data-bs-target="#maintenance" type="button">
                    <i class="fas fa-tools me-1"></i> Maintenance
                </button>
            </li>
        </ul>
    </div>

    <!-- Settings Content -->
    <div class="settings-content p-3">
        <div class="tab-content" id="settingsTabsContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h6 class="mb-4">General Settings</h6>
                    <form id="generalSettingsForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="appName" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="appName" name="app_name" value="<?php echo htmlspecialchars($settings['app_name'] ?? 'RideMax'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="appVersion" class="form-label">Application Version</label>
                                <input type="text" class="form-control" id="appVersion" name="app_version" value="<?php echo htmlspecialchars($settings['app_version'] ?? '1.0.0'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="defaultLanguage" class="form-label">Default Language</label>
                                <select class="form-select" id="defaultLanguage" name="default_language">
                                    <option value="en" <?php echo ($settings['default_language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="id" <?php echo ($settings['default_language'] ?? 'en') === 'id' ? 'selected' : ''; ?>>Indonesian</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="defaultCurrency" class="form-label">Default Currency</label>
                                <select class="form-select" id="defaultCurrency" name="default_currency">
                                    <option value="IDR" <?php echo ($settings['default_currency'] ?? 'IDR') === 'IDR' ? 'selected' : ''; ?>>Indonesian Rupiah (IDR)</option>
                                    <option value="USD" <?php echo ($settings['default_currency'] ?? 'IDR') === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="supportEmail" class="form-label">Support Email</label>
                            <input type="email" class="form-control" id="supportEmail" name="support_email" value="<?php echo htmlspecialchars($settings['support_email'] ?? 'support@ridemax.com'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="supportPhone" class="form-label">Support Phone</label>
                            <input type="tel" class="form-control" id="supportPhone" name="support_phone" value="<?php echo htmlspecialchars($settings['support_phone'] ?? '+62-800-123-4567'); ?>">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenanceMode">
                                Enable Maintenance Mode
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                    </form>
                </div>
            </div>

            <!-- Pricing Settings -->
            <div class="tab-pane fade" id="pricing" role="tabpanel">
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h6 class="mb-4">Pricing Configuration</h6>
                    <form id="pricingSettingsForm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="economyBasePrice" class="form-label">Economy Base Price (IDR)</label>
                                <input type="number" class="form-control" id="economyBasePrice" name="economy_base_price" value="<?php echo htmlspecialchars($settings['economy_base_price'] ?? '15000'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="comfortBasePrice" class="form-label">Comfort Base Price (IDR)</label>
                                <input type="number" class="form-control" id="comfortBasePrice" name="comfort_base_price" value="<?php echo htmlspecialchars($settings['comfort_base_price'] ?? '25000'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="premiumBasePrice" class="form-label">Premium Base Price (IDR)</label>
                                <input type="number" class="form-control" id="premiumBasePrice" name="premium_base_price" value="<?php echo htmlspecialchars($settings['premium_base_price'] ?? '40000'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="pricePerKm" class="form-label">Price per Kilometer (IDR)</label>
                                <input type="number" class="form-control" id="pricePerKm" name="price_per_km" value="<?php echo htmlspecialchars($settings['price_per_km'] ?? '3000'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="serviceFee" class="form-label">Service Fee (IDR)</label>
                                <input type="number" class="form-control" id="serviceFee" name="service_fee" value="<?php echo htmlspecialchars($settings['service_fee'] ?? '2000'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="driverCommission" class="form-label">Driver Commission (%)</label>
                                <input type="number" class="form-control" id="driverCommission" name="driver_commission" min="0" max="100" value="<?php echo htmlspecialchars($settings['driver_commission'] ?? '80'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="surgeMultiplier" class="form-label">Surge Pricing Multiplier</label>
                                <input type="number" class="form-control" id="surgeMultiplier" name="surge_multiplier" step="0.1" min="1" value="<?php echo htmlspecialchars($settings['surge_multiplier'] ?? '1.5'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cancellationFee" class="form-label">Cancellation Fee (IDR)</label>
                                <input type="number" class="form-control" id="cancellationFee" name="cancellation_fee" value="<?php echo htmlspecialchars($settings['cancellation_fee'] ?? '5000'); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Pricing Settings</button>
                    </form>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h6 class="mb-4">Notification Settings</h6>
                    <form id="notificationSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Email Notifications</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="emailNewOrder" name="email_new_order" <?php echo ($settings['email_new_order'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="emailNewOrder">
                                        New Order Notifications
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="emailOrderComplete" name="email_order_complete" <?php echo ($settings['email_order_complete'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="emailOrderComplete">
                                        Order Completion Notifications
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="emailNewUser" name="email_new_user" <?php echo ($settings['email_new_user'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="emailNewUser">
                                        New User Registration
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>SMS Notifications</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="smsOrderStatus" name="sms_order_status" <?php echo ($settings['sms_order_status'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="smsOrderStatus">
                                        Order Status Updates
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="smsDriverArrival" name="sms_driver_arrival" <?php echo ($settings['sms_driver_arrival'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="smsDriverArrival">
                                        Driver Arrival Notifications
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6>Push Notification Settings</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fcmServerKey" class="form-label">FCM Server Key</label>
                                <input type="password" class="form-control" id="fcmServerKey" name="fcm_server_key" value="<?php echo htmlspecialchars($settings['fcm_server_key'] ?? ''); ?>" placeholder="Enter FCM Server Key">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fcmSenderId" class="form-label">FCM Sender ID</label>
                                <input type="text" class="form-control" id="fcmSenderId" name="fcm_sender_id" value="<?php echo htmlspecialchars($settings['fcm_sender_id'] ?? ''); ?>" placeholder="Enter FCM Sender ID">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                    </form>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h6 class="mb-4">Security Configuration</h6>
                    <form id="securitySettingsForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="passwordMinLength" class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" id="passwordMinLength" name="password_min_length" min="6" max="20" value="<?php echo htmlspecialchars($settings['password_min_length'] ?? '6'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="loginAttempts" class="form-label">Max Login Attempts</label>
                                <input type="number" class="form-control" id="loginAttempts" name="max_login_attempts" min="3" max="10" value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sessionTimeout" class="form-label">Session Timeout (minutes)</label>
                                <input type="number" class="form-control" id="sessionTimeout" name="session_timeout" min="15" max="1440" value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '120'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lockoutDuration" class="form-label">Account Lockout Duration (minutes)</label>
                                <input type="number" class="form-control" id="lockoutDuration" name="lockout_duration" min="5" max="60" value="<?php echo htmlspecialchars($settings['lockout_duration'] ?? '15'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="requireEmailVerification" name="require_email_verification" <?php echo ($settings['require_email_verification'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requireEmailVerification">
                                Require Email Verification for New Users
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="requirePhoneVerification" name="require_phone_verification" <?php echo ($settings['require_phone_verification'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requirePhoneVerification">
                                Require Phone Verification for Drivers
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enableTwoFactor" name="enable_two_factor" <?php echo ($settings['enable_two_factor'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enableTwoFactor">
                                Enable Two-Factor Authentication for Admins
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Security Settings</button>
                    </form>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="tab-pane fade" id="maintenance" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="bg-white rounded-3 shadow-sm p-4">
                            <h6 class="mb-4">Database Maintenance</h6>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary w-100" onclick="optimizeDatabase()">
                                    <i class="fas fa-database me-2"></i>
                                    Optimize Database
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-warning w-100" onclick="cleanupLogs()">
                                    <i class="fas fa-broom me-2"></i>
                                    Cleanup Old Logs
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-info w-100" onclick="generateBackup()">
                                    <i class="fas fa-download me-2"></i>
                                    Generate Database Backup
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="bg-white rounded-3 shadow-sm p-4">
                            <h6 class="mb-4">System Information</h6>
                            
                            <table class="table table-sm">
                                <tr>
                                    <td>PHP Version:</td>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td>Server Software:</td>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td>Database Version:</td>
                                    <td id="dbVersion">Loading...</td>
                                </tr>
                                <tr>
                                    <td>Disk Space:</td>
                                    <td id="diskSpace">Loading...</td>
                                </tr>
                                <tr>
                                    <td>Memory Usage:</td>
                                    <td><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-3 shadow-sm p-4">
                    <h6 class="mb-4">System Logs</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Level</th>
                                    <th>Message</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody id="systemLogsTable">
                                <!-- Logs will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSystemInfo();
    loadSystemLogs();
    
    // Form submissions
    document.getElementById('generalSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings('general', this);
    });
    
    document.getElementById('pricingSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings('pricing', this);
    });
    
    document.getElementById('notificationSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings('notifications', this);
    });
    
    document.getElementById('securitySettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings('security', this);
    });
});

function saveSettings(category, form) {
    const formData = new FormData(form);
    formData.append('action', 'save_settings');
    formData.append('category', category);
    
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch('../process/admin_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Settings saved successfully!');
        } else {
            alert(data.message || 'Failed to save settings.');
        }
    })
    .catch(error => {
        console.error('Error saving settings:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function loadSystemInfo() {
    fetch('../process/admin_process.php?action=get_system_info')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('dbVersion').textContent = data.info.db_version || 'N/A';
                document.getElementById('diskSpace').textContent = data.info.disk_space || 'N/A';
            }
        })
        .catch(error => console.error('Error loading system info:', error));
}

function loadSystemLogs() {
    fetch('../process/admin_process.php?action=get_system_logs')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('systemLogsTable');
            
            if (data.success && data.logs.length > 0) {
                tbody.innerHTML = data.logs.map(log => `
                    <tr>
                        <td>${formatDate(log.created_at)}</td>
                        <td><span class="badge bg-${getLogLevelColor(log.level)}">${log.level.toUpperCase()}</span></td>
                        <td>${escapeHtml(log.message)}</td>
                        <td>${escapeHtml(log.ip_address || 'N/A')}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted">No logs available</td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
            document.getElementById('systemLogsTable').innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">Error loading logs</td>
                </tr>
            `;
        });
}

function optimizeDatabase() {
    if (confirm('This will optimize all database tables. Continue?')) {
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Optimizing...';
        
        fetch('../process/admin_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'optimize_database'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Database optimization completed successfully!');
            } else {
                alert(data.message || 'Database optimization failed.');
            }
        })
        .catch(error => {
            console.error('Error optimizing database:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-database me-2"></i>Optimize Database';
        });
    }
}

function cleanupLogs() {
    if (confirm('This will delete logs older than 30 days. Continue?')) {
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cleaning...';
        
        fetch('../process/admin_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cleanup_logs'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Log cleanup completed successfully!');
                loadSystemLogs();
            } else {
                alert(data.message || 'Log cleanup failed.');
            }
        })
        .catch(error => {
            console.error('Error cleaning logs:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-broom me-2"></i>Cleanup Old Logs';
        });
    }
}

function generateBackup() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    fetch('../process/admin_process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'generate_backup'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Download the backup file
            const a = document.createElement('a');
            a.href = data.backup_url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            alert('Database backup generated successfully!');
        } else {
            alert(data.message || 'Backup generation failed.');
        }
    })
    .catch(error => {
        console.error('Error generating backup:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download me-2"></i>Generate Database Backup';
    });
}

function getLogLevelColor(level) {
    const colors = {
        'info': 'primary',
        'warning': 'warning',
        'error': 'danger',
        'success': 'success'
    };
    return colors[level] || 'secondary';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../includes/footer.php'; ?>
