<?php
require_once 'auth.php';

$pdo = getDatabase();
$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$redirect = $_POST['redirect'] ?? '';

if (!$id || !$type) {
    $_SESSION['flash_error'] = 'Solicitud inválida.';
    header('Location: ' . ($redirect ?: 'dashboard.php'));
    exit;
}

try {
    if ($type === 'lead') {
        $stmt = $pdo->prepare("SELECT name FROM landing_leads WHERE id = ?");
        $stmt->execute([$id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lead) throw new Exception('Lead no encontrado.');

        $pdo->prepare("DELETE FROM lead_logs WHERE lead_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM landing_leads WHERE id = ?")->execute([$id]);

        $log = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'deleted', ?)");
        $log->execute([$id, "Lead {$lead['name']} eliminado manualmente."]);

        $_SESSION['flash_success'] = "Lead {$lead['name']} eliminado correctamente.";
    } elseif ($type === 'order') {
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) throw new Exception('Cliente no encontrado.');

        $pdo->prepare("DELETE FROM monthly_payments WHERE order_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);

        $name = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
        $_SESSION['flash_success'] = "Cliente {$name} eliminado correctamente.";
    } else {
        throw new Exception('Tipo de registro no válido.');
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
}

$redirect_url = $redirect ?: 'dashboard.php';
header("Location: $redirect_url");
exit;
