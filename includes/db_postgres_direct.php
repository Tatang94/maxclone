<?php
/**
 * PostgreSQL Database Connection
 * Direct PostgreSQL implementation for RideMax
 */

// Get database configuration from environment variables
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
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
    
} catch (PDOException $e) {
    // Log the error and fallback to SQLite
    error_log("PostgreSQL connection failed: " . $e->getMessage());
    
    // Reset to use SQLite
    $dbFile = __DIR__ . '/../database/ridemax.db';
    
    // Create database if it doesn't exist
    if (!file_exists($dbFile)) {
        $dbDir = dirname($dbFile);
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        require_once __DIR__ . '/../database/sqlite_setup.php';
    }
    
    try {
        $dsn = "sqlite:$dbFile";
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Execute a query with error handling
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return mixed Query result
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
?>