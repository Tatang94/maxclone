<?php
/**
 * PostgreSQL Database Connection Configuration
 * RideMax Super App - PostgreSQL PDO Database Connection
 * Enhanced version with PostgreSQL support
 */

// Get database configuration from environment variables
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

if (empty($databaseUrl)) {
    // Fallback to SQLite if PostgreSQL not available
    require_once __DIR__ . '/db.php';
    return;
}

// Parse DATABASE_URL
$dbConfig = parse_url($databaseUrl);

// PDO options for security and performance
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => true
];

try {
    // Create PostgreSQL DSN
    $host = $dbConfig['host'];
    $port = $dbConfig['port'] ?? 5432;
    $dbname = ltrim($dbConfig['path'], '/');
    $username = $dbConfig['user'];
    $password = $dbConfig['pass'];
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    // Create PDO connection for PostgreSQL
    $pdo = new PDO($dsn, $username, $password, $pdo_options);
    
    // Initialize database schema if needed
    initializePostgreSQLSchema($pdo);
    
} catch (PDOException $e) {
    // Log the error
    error_log("PostgreSQL connection failed: " . $e->getMessage());
    
    // Fallback to SQLite
    require_once __DIR__ . '/db.php';
    return;
}

/**
 * Initialize PostgreSQL database schema
 */
function initializePostgreSQLSchema($pdo) {
    
    try {
        // Check if tables exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'users'");
        $tableExists = $stmt->fetchColumn() > 0;
        
        if (!$tableExists) {
            // Read and execute PostgreSQL schema
            $schemaFile = __DIR__ . '/../database/postgres_schema.sql';
            if (file_exists($schemaFile)) {
                $schema = file_get_contents($schemaFile);
                $pdo->exec($schema);
                
                // Insert default admin user
                insertDefaultAdmin($pdo);
                
                // Insert default settings
                insertDefaultSettings($pdo);
            }
        }
    } catch (PDOException $e) {
        error_log("Schema initialization failed: " . $e->getMessage());
    }
}

/**
 * Insert default admin user
 */
function insertDefaultAdmin($pdo) {
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@ridemax.com'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $adminData = [
                'name' => 'Administrator',
                'email' => 'admin@ridemax.com',
                'phone' => '+628123456789',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'user_type' => 'admin',
                'email_verified' => true,
                'is_active' => true
            ];
            
            $columns = implode(', ', array_keys($adminData));
            $placeholders = ':' . implode(', :', array_keys($adminData));
            
            $query = "INSERT INTO users ({$columns}) VALUES ({$placeholders})";
            $stmt = $pdo->prepare($query);
            $stmt->execute($adminData);
        }
    } catch (PDOException $e) {
        error_log("Failed to insert default admin: " . $e->getMessage());
    }
}

/**
 * Insert default system settings
 */
function insertDefaultSettings($pdo) {
    
    $defaultSettings = [
        ['app_name', 'RideMax', 'string', 'Application name'],
        ['base_fare', '5000', 'number', 'Base fare in IDR'],
        ['per_km_rate', '2000', 'number', 'Rate per kilometer in IDR'],
        ['commission_rate', '0.15', 'number', 'Driver commission rate (0-1)'],
        ['max_distance', '50', 'number', 'Maximum booking distance in KM'],
        ['booking_timeout', '300', 'number', 'Booking timeout in seconds'],
        ['payment_methods', '["cash","wallet","qris"]', 'json', 'Available payment methods'],
        ['vehicle_types', '["economy","comfort","premium"]', 'json', 'Available vehicle types']
    ];
    
    try {
        foreach ($defaultSettings as $setting) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$setting[0]]);
            
            if ($stmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
                $stmt->execute($setting);
            }
        }
    } catch (PDOException $e) {
        error_log("Failed to insert default settings: " . $e->getMessage());
    }
}

// Include common database functions
require_once __DIR__ . '/db_functions.php';
?>