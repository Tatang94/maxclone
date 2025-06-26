<?php
/**
 * Common Database Functions
 * Works with both SQLite and PostgreSQL
 */

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
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false Single row or false if not found
 */
function fetchSingle($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

/**
 * Get multiple rows from database
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Multiple rows
 */
function fetchMultiple($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

/**
 * Get single value from database
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return mixed Single value
 */
function fetchValue($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert data and return last insert ID
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int Last insert ID
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
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause
 * @param array $whereParams Parameters for WHERE clause
 * @return int Number of affected rows
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
 * @param string $table Table name
 * @param string $where WHERE clause
 * @param array $params Parameters for WHERE clause
 * @return int Number of affected rows
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
 * @return bool
 */
function inTransaction() {
    global $pdo;
    return $pdo->inTransaction();
}

/**
 * Get database type (sqlite or pgsql)
 * @return string
 */
function getDatabaseType() {
    global $pdo;
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
}

/**
 * Get current timestamp function based on database type
 * @return string
 */
function getCurrentTimestamp() {
    global $pdo;
    $dbType = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    return $dbType === 'sqlite' ? "datetime('now')" : 'CURRENT_TIMESTAMP';
}
?>