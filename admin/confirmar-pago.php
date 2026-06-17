<?php
// API para confirmar pagos por transferencia
// Archivo: admin/confirmar-pago.php

session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once '../php/config/config.php';
require_once '../php/config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['payment_id'])) {
        throw new Exception('Payment ID requerido');
    }
    
    $paymentId = (int)$input['payment_id'];
    
    $pdo = getDatabase();
    
    // Obtener datos del pago
    $stmt = $pdo->prepare("
        SELECT mp.*, o.email, o.first_name, o.last_name, o.project_name
        FROM monthly_payments mp
        JOIN orders o ON mp.order_id = o.id
        WHERE mp.id = ? AND mp.status = 'pending' AND mp.payment_method = 'transfer'
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Pago no encontrado o ya confirmado');
    }
    
    // Actualizar estado del pago
    $stmt = $pdo->prepare("
        UPDATE monthly_payments 
        SET status = 'paid', 
            paid_at = NOW(),
            notes = CONCAT(COALESCE(notes, ''), ' | Confirmado manualmente por admin el ', NOW())
        WHERE id = ?
    ");
    $stmt->execute([$paymentId]);
    
    // Registrar en logs
    $stmt = $pdo->prepare("
        INSERT INTO payment_logs (commerce_order, event_type, event_data, created_at)
        VALUES (?, 'transfer_confirmed', ?, NOW())
    ");
    $stmt->execute([
        $payment['commerce_order'],
        json_encode([
            'payment_id' => $paymentId,
            'confirmed_by' => $_SESSION['user_id'],
            'email' => $payment['email'],
            'amount' => $payment['amount']
        ])
    ]);
    
    // Enviar email de confirmación al cliente
    $to = $payment['email'];
    $subject = "IPStream - Pago Confirmado";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-icon { font-size: 64px; text-align: center; margin: 20px 0; }
            .details { background: white; padding: 20px; border-left: 4px solid #10b981; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>¡Pago Confirmado!</h1>
                <p>Tu transferencia ha sido recibida</p>
            </div>
            <div class='content'>
                <div class='success-icon'>✅</div>
                
                <p>Hola <strong>{$payment['first_name']} {$payment['last_name']}</strong>,</p>
                
                <p>¡Excelentes noticias! Hemos confirmado la recepción de tu transferencia bancaria.</p>
                
                <div class='details'>
                    <h3>📋 Detalles del Pago</h3>
                    <p><strong>Proyecto:</strong> {$payment['project_name']}</p>
                    <p><strong>Monto:</strong> $" . number_format($payment['amount'], 0, ',', '.') . " CLP</p>
                    <p><strong>N° de Orden:</strong> {$payment['commerce_order']}</p>
                    <p><strong>Fecha de Confirmación:</strong> " . date('d/m/Y H:i') . "</p>
                </div>
                
                <p>Tu servicio ya está activo y puedes continuar disfrutando de IPStream sin interrupciones.</p>
                
                <p>Gracias por tu preferencia.</p>
                
                <div class='footer'>
                    <p>¿Necesitas ayuda? Contáctanos:</p>
                    <p>📧 soporte@ipstream.cl | 📱 WhatsApp: +56 9 2191 1216</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: IPStream <noreply@ipstream.cl>" . "\r\n";
    
    mail($to, $subject, $message, $headers);
    
    echo json_encode([
        'success' => true,
        'message' => 'Pago confirmado exitosamente'
    ]);
    
} catch (Exception $e) {
    error_log('Error al confirmar pago: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
