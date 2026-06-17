<?php
require_once 'auth.php';
require_once __DIR__ . '/../php/config/config.php'; // Explicitly include config.php
require_once __DIR__ . '/../php/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $pdo = getDatabase();

    $id = $_POST['id'] ?? null;
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $project_name = $_POST['project_name'] ?? '';
    $plan_id = $_POST['plan_id'] ?? null;
    $billing_type = $_POST['billing_type'] ?? '';
    $status = $_POST['status'] ?? 'active'; // Default status for new subscriptions

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($whatsapp) || empty($project_name) || empty($plan_id) || empty($billing_type)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Todos los campos obligatorios deben ser completados.'];
        header('Location: suscripcion_editar.php' . ($id ? '?id=' . $id : ''));
        exit;
    }

    // Fetch plan details to get price, plan_name, plan_type
    $stmt_plan = $pdo->prepare("SELECT plan_name, price, plan_key FROM plans WHERE id = ?");
    $stmt_plan->execute([$plan_id]);
    $plan_details = $stmt_plan->fetch(PDO::FETCH_ASSOC);

    if (!$plan_details) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Plan seleccionado no válido.'];
        header('Location: suscripcion_editar.php' . ($id ? '?id=' . $id : ''));
        exit;
    }

    $plan_name = $plan_details['plan_name'];
    $amount = $plan_details['price'];
    // Determine plan_type from plan_key (e.g., 'radio_monthly' -> 'radio')
    $plan_type = explode('_', $plan_details['plan_key'])[0];

    try {
        if ($id) {
            // Update existing subscription
            $stmt = $pdo->prepare("UPDATE orders SET first_name = ?, last_name = ?, email = ?, whatsapp = ?, project_name = ?, plan_id = ?, billing_type = ?, plan_name = ?, amount = ?, plan_type = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $whatsapp, $project_name, $plan_id, $billing_type, $plan_name, $amount, $plan_type, $status, $id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Suscripción actualizada exitosamente.'];
        } else {
            // Insert new subscription
            $commerce_order = 'ADM-' . uniqid(); // Generate a unique commerce_order for admin-created subscriptions
            $stmt = $pdo->prepare("INSERT INTO orders (commerce_order, first_name, last_name, email, whatsapp, project_name, plan_id, billing_type, plan_name, amount, plan_type, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $stmt->execute([$commerce_order, $first_name, $last_name, $email, $whatsapp, $project_name, $plan_id, $billing_type, $plan_name, $amount, $plan_type, $status]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Suscripción creada exitosamente.'];
        }
        header('Location: suscripciones.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error de base de datos: ' . $e->getMessage()];
        header('Location: suscripcion_editar.php' . ($id ? '?id=' . $id : ''));
        exit;
    }
} else {
    header('Location: suscripciones.php');
    exit;
}
