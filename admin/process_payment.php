<?php
require_once 'auth.php';
require_once __DIR__ . '/../php/config/config.php'; // Explicitly include config.php
require_once __DIR__ . '/../php/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDatabase();

    $order_id = $_POST['order_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $paid_at = $_POST['paid_at'] ?? null;
    $status = $_POST['status'] ?? 'paid';

    // Validate required fields
    if (empty($order_id) || empty($amount) || empty($paid_at)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Todos los campos obligatorios deben ser completados.'];
        header('Location: registrar_pago.php' . ($order_id ? '?subscription_id=' . $order_id : ''));
        exit;
    }

    try {
        // Insert new payment record
        $commerce_order = 'PAY-' . uniqid(); // Unique ID for this manual payment
        $stmt = $pdo->prepare("INSERT INTO monthly_payments (order_id, commerce_order, amount, status, paid_at, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$order_id, $commerce_order, $amount, $status, $paid_at]);

        // If payment is 'paid', update the order status to 'active' if it's not already
        if ($status === 'paid') {
            $stmt_update_order = $pdo->prepare("UPDATE orders SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status != 'active'");
            $stmt_update_order->execute([$order_id]);
        }

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Pago registrado exitosamente.'];
        header('Location: suscripcion_detalle.php?id=' . $order_id);
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error de base de datos: ' . $e->getMessage()];
        header('Location: registrar_pago.php' . ($order_id ? '?subscription_id=' . $order_id : ''));
        exit;
    }
} else {
    header('Location: suscripciones.php');
    exit;
}
