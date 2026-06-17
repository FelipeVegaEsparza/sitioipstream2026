<?php
// Configuración de base de datos
// Archivo: config/database.php

function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                break;
            } catch (PDOException $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    error_log('Database connection failed after ' . $maxRetries . ' attempts: ' . $e->getMessage());
                    throw new Exception('Database connection failed: ' . $e->getMessage());
                }
                error_log('Database connection attempt ' . $attempt . ' failed, retrying: ' . $e->getMessage());
                sleep(1);
            }
        }
    }
    
    return $pdo;
}

// Función para probar la conexión
function testDatabaseConnection() {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        return $result['test'] === 1;
    } catch (Exception $e) {
        error_log('Database test failed: ' . $e->getMessage());
        return false;
    }
}

// Alias para compatibilidad con APIs
function getDBConnection() {
    return getDatabase();
}

// Función adicional para debug
function getDatabaseInfo() {
    try {
        $pdo = getDatabase();
        
        // Verificar tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return [
            'connected' => true,
            'tables' => $tables,
            'has_orders' => in_array('orders', $tables),
            'has_logs' => in_array('payment_logs', $tables)
        ];
    } catch (Exception $e) {
        return [
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>