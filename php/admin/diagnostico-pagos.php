<?php
// Script de diagnóstico para pagos
// Acceso: /php/admin/diagnostico-pagos.php

require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

// Verificar si hay un token específico para consultar
$tokenToCheck = $_GET['token'] ?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Pagos - IPStream</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 32px;
        }
        h2 {
            color: #333;
            margin: 30px 0 15px 0;
            font-size: 24px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.warning { background: #fff3cd; color: #856404; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .code {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Pagos - IPStream</h1>
        
        <?php
        try {
            $pdo = getDatabase();
            
            // 1. Verificar configuración
            echo '<div class="section">';
            echo '<h2>⚙️ Configuración Actual</h2>';
            echo '<div class="code">';
            echo 'Flow API URL: ' . FLOW_API_URL . '<br>';
            echo 'Flow API Key: ' . substr(FLOW_API_KEY, 0, 10) . '...<br>';
            echo 'DB Host: ' . DB_HOST . '<br>';
            echo 'DB Name: ' . DB_NAME . '<br>';
            echo 'Site URL: ' . SITE_URL . '<br>';
            echo 'Webhook URL: ' . SITE_URL . '/php/api/flow/webhook.php<br>';
            echo '</div>';
            echo '</div>';
            
            // 2. Últimas órdenes
            echo '<div class="section">';
            echo '<h2>📋 Últimas 10 Órdenes</h2>';
            $stmt = $pdo->query("
                SELECT * FROM orders 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)) {
                echo '<div class="alert alert-warning">⚠️ No hay órdenes registradas en la base de datos.</div>';
            } else {
                echo '<table>';
                echo '<tr>
                    <th>ID</th>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Plan</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Creada</th>
                    <th>Pagada</th>
                </tr>';
                
                foreach ($orders as $order) {
                    $statusClass = 'warning';
                    if ($order['status'] === 'active' || $order['status'] === 'paid') $statusClass = 'success';
                    if ($order['status'] === 'failed') $statusClass = 'error';
                    
                    echo '<tr>';
                    echo '<td>' . $order['id'] . '</td>';
                    echo '<td><small>' . htmlspecialchars($order['commerce_order']) . '</small></td>';
                    echo '<td>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '<br><small>' . htmlspecialchars($order['email']) . '</small></td>';
                    echo '<td>' . htmlspecialchars($order['plan_name']) . '</td>';
                    echo '<td>$' . number_format($order['amount'], 0, ',', '.') . '</td>';
                    echo '<td><span class="status ' . $statusClass . '">' . strtoupper($order['status']) . '</span></td>';
                    echo '<td><small>' . date('d/m/Y H:i', strtotime($order['created_at'])) . '</small></td>';
                    echo '<td><small>' . ($order['paid_at'] ? date('d/m/Y H:i', strtotime($order['paid_at'])) : '-') . '</small></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            echo '</div>';
            
            // 3. Logs de eventos
            echo '<div class="section">';
            echo '<h2>📝 Últimos 20 Eventos de Pago</h2>';
            $stmt = $pdo->query("
                SELECT * FROM payment_logs 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($logs)) {
                echo '<div class="alert alert-warning">⚠️ No hay logs de eventos registrados.</div>';
            } else {
                echo '<table>';
                echo '<tr>
                    <th>ID</th>
                    <th>Orden</th>
                    <th>Evento</th>
                    <th>Datos</th>
                    <th>Fecha</th>
                </tr>';
                
                foreach ($logs as $log) {
                    echo '<tr>';
                    echo '<td>' . $log['id'] . '</td>';
                    echo '<td><small>' . htmlspecialchars($log['commerce_order']) . '</small></td>';
                    echo '<td><strong>' . htmlspecialchars($log['event_type']) . '</strong></td>';
                    echo '<td><pre>' . htmlspecialchars(json_encode(json_decode($log['event_data']), JSON_PRETTY_PRINT)) . '</pre></td>';
                    echo '<td><small>' . date('d/m/Y H:i:s', strtotime($log['created_at'])) . '</small></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            echo '</div>';
            
            // 4. Órdenes pendientes
            echo '<div class="section">';
            echo '<h2>⏳ Órdenes Pendientes (más de 1 hora)</h2>';
            $stmt = $pdo->query("
                SELECT * FROM orders 
                WHERE status = 'pending' 
                AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY created_at DESC
            ");
            $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($pendingOrders)) {
                echo '<div class="alert alert-success">✅ No hay órdenes pendientes antiguas.</div>';
            } else {
                echo '<div class="alert alert-warning">⚠️ Hay ' . count($pendingOrders) . ' órdenes pendientes de más de 1 hora. Esto puede indicar que el webhook no está funcionando.</div>';
                echo '<table>';
                echo '<tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Plan</th>
                    <th>Monto</th>
                    <th>Creada hace</th>
                    <th>Token Flow</th>
                </tr>';
                
                foreach ($pendingOrders as $order) {
                    $hoursAgo = round((time() - strtotime($order['created_at'])) / 3600, 1);
                    echo '<tr>';
                    echo '<td><small>' . htmlspecialchars($order['commerce_order']) . '</small></td>';
                    echo '<td>' . htmlspecialchars($order['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($order['plan_name']) . '</td>';
                    echo '<td>$' . number_format($order['amount'], 0, ',', '.') . '</td>';
                    echo '<td>' . $hoursAgo . ' horas</td>';
                    echo '<td>' . ($order['flow_token'] ? '✅' : '❌') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            echo '</div>';
            
            // 5. Estadísticas
            echo '<div class="section">';
            echo '<h2>📊 Estadísticas</h2>';
            
            $stats = [];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
            $stats['total'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
            $stats['pending'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'paid'");
            $stats['paid'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'active'");
            $stats['active'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'failed'");
            $stats['failed'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT SUM(amount) as total FROM orders WHERE status IN ('paid', 'active')");
            $stats['revenue'] = $stmt->fetch()['total'] ?? 0;
            
            echo '<div class="code">';
            echo 'Total de órdenes: <strong>' . $stats['total'] . '</strong><br>';
            echo 'Pendientes: <strong>' . $stats['pending'] . '</strong><br>';
            echo 'Pagadas: <strong>' . $stats['paid'] . '</strong><br>';
            echo 'Activas: <strong>' . $stats['active'] . '</strong><br>';
            echo 'Fallidas: <strong>' . $stats['failed'] . '</strong><br>';
            echo 'Ingresos totales: <strong>$' . number_format($stats['revenue'], 0, ',', '.') . ' CLP</strong><br>';
            echo '</div>';
            echo '</div>';
            
            // 6. Verificar webhook
            echo '<div class="section">';
            echo '<h2>🔗 Verificación de Webhook</h2>';
            echo '<div class="alert alert-info">';
            echo '<strong>URL del Webhook:</strong><br>';
            echo SITE_URL . '/php/api/flow/webhook.php<br><br>';
            echo '<strong>Verifica que:</strong><br>';
            echo '1. Esta URL sea accesible públicamente<br>';
            echo '2. Esté configurada correctamente en Flow.cl<br>';
            echo '3. El servidor tenga permisos de escritura en logs<br>';
            echo '4. Las credenciales de Flow sean correctas<br>';
            echo '</div>';
            
            // Verificar si el archivo webhook existe
            $webhookPath = __DIR__ . '/../api/flow/webhook.php';
            if (file_exists($webhookPath)) {
                echo '<div class="alert alert-success">✅ Archivo webhook.php existe</div>';
            } else {
                echo '<div class="alert alert-danger">❌ Archivo webhook.php NO encontrado en: ' . $webhookPath . '</div>';
            }
            
            // Verificar permisos de escritura
            $logPath = __DIR__ . '/../logs';
            if (is_writable($logPath)) {
                echo '<div class="alert alert-success">✅ Directorio de logs tiene permisos de escritura</div>';
            } else {
                echo '<div class="alert alert-warning">⚠️ Directorio de logs puede no tener permisos de escritura</div>';
            }
            
            echo '</div>';
            
            // 7. Consultar pago específico en Flow
            if ($tokenToCheck) {
                echo '<div class="section">';
                echo '<h2>🔍 Consultar Pago en Flow</h2>';
                
                $statusParams = [
                    'apiKey' => FLOW_API_KEY,
                    'token' => $tokenToCheck
                ];
                
                ksort($statusParams);
                $queryParts = [];
                foreach ($statusParams as $key => $value) {
                    $queryParts[] = $key . '=' . $value;
                }
                $paramString = implode('&', $queryParts);
                $signature = hash_hmac('sha256', $paramString, FLOW_SECRET_KEY);
                $statusParams['s'] = $signature;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, FLOW_API_URL . '/payment/getStatus');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($statusParams));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo '<div class="code">';
                echo 'HTTP Code: ' . $httpCode . '<br>';
                echo 'Response:<br>';
                echo '</div>';
                echo '<pre>' . htmlspecialchars(json_encode(json_decode($response), JSON_PRETTY_PRINT)) . '</pre>';
                
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">';
            echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h2>🛠️ Acciones</h2>
            <a href="?" class="btn">🔄 Recargar</a>
            <a href="../test-connection.php" class="btn">🧪 Test Conexión BD</a>
            <a href="../../admin/dashboard.php" class="btn">📊 Dashboard Admin</a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #6c757d; font-size: 14px;">
            <p>IPStream - Diagnóstico de Pagos</p>
            <p>Generado: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
