<?php
/**
 * SQLite Database Setup for RideMax
 * Membuat database SQLite untuk kompatibilitas Replit
 */

// Buat direktori database jika belum ada
$dbDir = __DIR__;
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$dbFile = $dbDir . '/ridemax.db';

try {
    // Koneksi ke SQLite
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Baca dan eksekusi schema SQLite
    $schema = file_get_contents(__DIR__ . '/sqlite_schema.sql');
    $pdo->exec($schema);
    
    echo "Database SQLite berhasil dibuat di: $dbFile\n";
    echo "Schema berhasil dijalankan.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>