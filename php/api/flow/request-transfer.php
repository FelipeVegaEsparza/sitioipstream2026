<?php
// API para solicitar pago por transferencia bancaria
// Archivo: php/api/flow/request-transfer.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Cargar configuración
require_once '../../config/config.php';
require_once '../../config/database.php';

try {
    error_log('💳 Procesando solicitud de transferencia...');
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validar datos requeridos
    $required = ['order_id', 'email', 'first_name', 'last_name', 'project_name', 'plan_name', 'amount'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    error_log("📧 Solicitud de transferencia para: {$input['email']}");
    
    // Conectar a MySQL
    $pdo = getDatabase();
    
    // Generar número de orden único
    $commerceOrder = 'IPStream-Transfer-' . time() . '-' . rand(100, 999);
    
    // Crear registro de pago pendiente por transferencia
    $stmt = $pdo->prepare("
        INSERT INTO monthly_payments (
            order_id, commerce_order, amount, status, payment_method, notes, created_at
        ) VALUES (?, ?, ?, 'pending', 'transfer', 'Esperando comprobante de transferencia', NOW())
    ");
    
    $stmt->execute([
        $input['order_id'],
        $commerceOrder,
        $input['amount']
    ]);
    
    $paymentId = $pdo->lastInsertId();
    
    error_log("✅ Pago pendiente registrado: $commerceOrder (ID: $paymentId)");
    
    // Datos bancarios (CONFIGURA TUS DATOS REALES AQUÍ)
    $bankData = [
        'bank_name' => 'Banco Estado',
        'account_type' => 'Cuenta Corriente',
        'account_number' => '12345678',
        'account_holder' => 'IPStream SpA',
        'rut' => '76.XXX.XXX-X',
        'email_contact' => 'pagos@ipstream.cl'
    ];
    
    // Enviar email con datos bancarios
    $to = $input['email'];
    $subject = "IPStream - Datos para Transferencia Bancaria";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .bank-data { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
            .bank-data h3 { margin-top: 0; color: #667eea; }
            .bank-data p { margin: 10px 0; }
            .bank-data strong { color: #333; }
            .amount { font-size: 32px; color: #667eea; font-weight: bold; text-align: center; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
            .button { display: inline-block; padding: 12px 30px; background: #25D366; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Datos para Transferencia</h1>
                <p>IPStream - Tu Radio Online</p>
            </div>
            <div class='content'>
                <p>Hola <strong>{$input['first_name']} {$input['last_name']}</strong>,</p>
                
                <p>Gracias por elegir pagar mediante transferencia bancaria. A continuación encontrarás los datos para realizar tu pago:</p>
                
                <div class='bank-data'>
                    <h3>📋 Datos Bancarios</h3>
                    <p><strong>Banco:</strong> {$bankData['bank_name']}</p>
                    <p><strong>Tipo de Cuenta:</strong> {$bankData['account_type']}</p>
                    <p><strong>Número de Cuenta:</strong> {$bankData['account_number']}</p>
                    <p><strong>Titular:</strong> {$bankData['account_holder']}</p>
                    <p><strong>RUT:</strong> {$bankData['rut']}</p>
                </div>
                
                <div class='amount'>
                    $" . number_format($input['amount'], 0, ',', '.') . " CLP
                </div>
                
                <div class='bank-data'>
                    <h3>📝 Detalles del Pago</h3>
                    <p><strong>Proyecto:</strong> {$input['project_name']}</p>
                    <p><strong>Plan:</strong> {$input['plan_name']}</p>
                    <p><strong>N° de Orden:</strong> $commerceOrder</p>
                </div>
                
                <h3>⚠️ Importante:</h3>
                <ul>
                    <li>Realiza la transferencia por el monto exacto indicado</li>
                    <li>Una vez realizada la transferencia, envíanos el comprobante</li>
                    <li>Tu servicio se activará dentro de las 24 horas hábiles siguientes a la confirmación del pago</li>
                </ul>
                
                <h3>📤 Enviar Comprobante:</h3>
                <p>Puedes enviarnos el comprobante por cualquiera de estos medios:</p>
                
                <p style='text-align: center;'>
                    <a href='https://wa.me/56921911216?text=Hola,%20adjunto%20comprobante%20de%20transferencia%20para%20{$input['project_name']}%20-%20Orden:%20$commerceOrder' class='button'>
                        📱 Enviar por WhatsApp
                    </a>
                </p>
                
                <p style='text-align: center;'>
                    <strong>Email:</strong> {$bankData['email_contact']}<br>
                    <small>Asunto: Comprobante - $commerceOrder</small>
                </p>
                
                <div class='footer'>
                    <p>¿Necesitas ayuda? Contáctanos:</p>
                    <p>📧 {$bankData['email_contact']} | 📱 WhatsApp: +56 9 2191 1216</p>
                    <p style='margin-top: 20px; color: #999; font-size: 12px;'>
                        Este es un correo automático, por favor no respondas a esta dirección.
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers para email HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: IPStream <noreply@ipstream.cl>" . "\r\n";
    
    // Enviar email
    $emailSent = mail($to, $subject, $message, $headers);
    
    if ($emailSent) {
        error_log("📧 Email enviado a: {$input['email']}");
    } else {
        error_log("⚠️ No se pudo enviar el email a: {$input['email']}");
    }
    
    // Log del evento
    $stmt = $pdo->prepare("
        INSERT INTO payment_logs (commerce_order, event_type, event_data, created_at)
        VALUES (?, 'transfer_requested', ?, NOW())
    ");
    $stmt->execute([
        $commerceOrder,
        json_encode([
            'email' => $input['email'],
            'project_name' => $input['project_name'],
            'amount' => $input['amount'],
            'email_sent' => $emailSent
        ])
    ]);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Datos bancarios enviados por email',
        'commerce_order' => $commerceOrder,
        'payment_id' => $paymentId,
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    error_log('💥 Error en solicitud de transferencia: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
