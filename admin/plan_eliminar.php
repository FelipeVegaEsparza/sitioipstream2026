<?php
require_once 'auth.php';

$plan_id = $_GET['id'] ?? null;

if (!$plan_id) {
    header('Location: planes.php');
    exit;
}

$pdo = getDatabase();

try {
    // Verificar si el plan tiene órdenes asociadas
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE plan_id = ?");
    $stmt_check->execute([$plan_id]);
    $order_count = $stmt_check->fetchColumn();
    
    if ($order_count > 0) {
        $_SESSION['error'] = "No se puede eliminar el plan porque tiene {$order_count} orden(es) asociada(s). Desactívalo en su lugar.";
    } else {
        // Obtener información del plan para eliminar la imagen
        $stmt_plan = $pdo->prepare("SELECT image_url FROM plans WHERE id = ?");
        $stmt_plan->execute([$plan_id]);
        $plan = $stmt_plan->fetch(PDO::FETCH_ASSOC);
        
        // Eliminar la imagen si existe
        if ($plan && !empty($plan['image_url'])) {
            $image_path = __DIR__ . '/..' . $plan['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Eliminar el plan
        $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        
        $_SESSION['success'] = "Plan eliminado exitosamente";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error al eliminar el plan: " . $e->getMessage();
}

header('Location: planes.php');
exit;
?>
