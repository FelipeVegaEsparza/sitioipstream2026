<?php
require_once 'auth.php';

if (!isset($_GET['id'])) {
    header('Location: tutoriales.php');
    exit;
}

$pdo = getDatabase();

try {
    $stmt = $pdo->prepare("DELETE FROM tutorials WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    header('Location: tutoriales.php');
} catch (Exception $e) {
    die('Error al eliminar: ' . $e->getMessage());
}
