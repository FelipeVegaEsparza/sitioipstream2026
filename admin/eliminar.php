<?php
require_once 'auth.php';

$pdo = getDatabase();
requireCsrf();
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
    } elseif ($type === 'client_portfolio') {
        $stmt = $pdo->prepare("SELECT title, image_url FROM client_portfolio WHERE id = ?");
        $stmt->execute([$id]);
        $cp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cp) throw new Exception('Cliente no encontrado.');

        if ($cp['image_url'] && str_starts_with($cp['image_url'], '/uploads/portfolio/')) {
            $file = __DIR__ . '/..' . $cp['image_url'];
            if (file_exists($file)) unlink($file);
        }

        $pdo->prepare("DELETE FROM client_portfolio WHERE id = ?")->execute([$id]);
        $_SESSION['flash_success'] = "{$cp['title']} eliminado del portafolio.";
    } else {
        throw new Exception('Tipo de registro no válido.');
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
}

$redirect_url = $redirect ?: 'dashboard.php';
header("Location: $redirect_url");
exit;
