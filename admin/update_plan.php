<?php
require_once 'auth.php';
require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

requireCsrf();

$plan_id = $_POST['plan_id'] ?? null;
$price = $_POST['price'] ?? null;

if (!$plan_id || !is_numeric($price) || $price < 0) {
    // Basic validation failed, redirect back
    // In a real app, you'd have more robust error handling with session messages
    header('Location: edit_plan.php?id=' . $plan_id . '&error=invalid_data');
    exit;
}

try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("UPDATE plans SET price = ? WHERE id = ?");
    $stmt->execute([$price, $plan_id]);

    // Redirect to dashboard on success
    header('Location: dashboard.php?success=update');
    exit;

} catch (PDOException $e) {
    // In a real app, log this error
    error_log("Error updating plan: " . $e->getMessage());
    header('Location: edit_plan.php?id=' . $plan_id . '&error=db_error');
    exit;
}
?>