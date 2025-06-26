<?php
/**
 * Database Configuration Handler
 * Auto-detects and configures the best available database
 */

// Check if PostgreSQL is available
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

if (!empty($databaseUrl)) {
    // Use PostgreSQL if available
    require_once __DIR__ . '/db_postgres.php';
} else {
    // Fallback to SQLite
    require_once __DIR__ . '/db.php';
}
?>