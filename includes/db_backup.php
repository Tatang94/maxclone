<?php
/**
 * Database Connection Configuration
 * RideMax Super App - Hybrid PostgreSQL/SQLite Support
 */

// Check if PostgreSQL is available
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
$pdo = null;
$databaseType = 'sqlite';

if (!empty($databaseUrl)) {
    // Try PostgreSQL connection
    $dbConfig = parse_url($databaseUrl);
    
    $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    try {
        $host = $dbConfig['host'];
        $port = $dbConfig['port'] ?? 5432;
        $dbname = ltrim($dbConfig['path'], '/');
        $username = $dbConfig['user'];
        $password = $dbConfig['pass'];
        
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $username, $password, $pdo_options);
        
        $databaseType = 'postgresql';
        
    } catch (PDOException $e) {
        error_log("PostgreSQL connection failed, using SQLite: " . $e->getMessage());
        $pdo = null;
    }
}

// Fallback to SQLite if PostgreSQL failed or not available
if (!$pdo) {
    $databaseType = 'sqlite';
    $dbFile = __DIR__ . '/../database/ridemax.db';
    
    if (!file_exists($dbFile)) {
        $dbDir = dirname($dbFile);
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        require_once __DIR__ . '/../database/sqlite_setup.php';
    }
    
    $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    try {
        $dsn = "sqlite:$dbFile";
        $pdo = new PDO($dsn, null, null, $pdo_options);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Koneksi database gagal. Silakan coba lagi nanti.");
    }
}

/**
 * Execute a query with error handling
 */
function executeQuery($query, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage() . " | Query: " . $query);
        throw new Exception("Database query failed");
    }
}

/**
 * Get single row from database
 */
function fetchSingle($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

/**
 * Get multiple rows from database
 */
function fetchMultiple($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

/**
 * Get single value from database
 */
function fetchValue($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert data and return last insert ID
 */
function insertData($table, $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($data);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Insert failed: " . $e->getMessage() . " | Query: " . $query);
        throw new Exception("Failed to insert data");
    }
}

/**
 * Update data in database
 */
function updateData($table, $data, $where, $whereParams = []) {
    global $pdo;
    
    $setParts = [];
    foreach (array_keys($data) as $column) {
        $setParts[] = "{$column} = :{$column}";
    }
    $setClause = implode(', ', $setParts);
    
    $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
    
    try {
        $stmt = $pdo->prepare($query);
        $params = array_merge($data, $whereParams);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Update failed: " . $e->getMessage() . " | Query: " . $query);
        throw new Exception("Failed to update data");
    }
}

/**
 * Delete data from database
 */
function deleteData($table, $where, $params = []) {
    global $pdo;
    
    $query = "DELETE FROM {$table} WHERE {$where}";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Delete failed: " . $e->getMessage() . " | Query: " . $query);
        throw new Exception("Failed to delete data");
    }
}

/**
 * Begin database transaction
 */
function beginTransaction() {
    global $pdo;
    $pdo->beginTransaction();
}

/**
 * Commit database transaction
 */
function commitTransaction() {
    global $pdo;
    $pdo->commit();
}

/**
 * Rollback database transaction
 */
function rollbackTransaction() {
    global $pdo;
    $pdo->rollBack();
}

/**
 * Check if we're in a transaction
 */
function inTransaction() {
    global $pdo;
    return $pdo->inTransaction();
}

/**
 * Get database type
 */
function getDatabaseType() {
    global $databaseType;
    return $databaseType;
}
?>