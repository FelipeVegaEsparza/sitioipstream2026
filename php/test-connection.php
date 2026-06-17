<?php
// Script de prueba de conexión a MySQL con Docker
// Accede a: http://localhost:4321/php/test-connection.php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - IPStream</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #495057;
        }
        .value {
            color: #6c757d;
            font-family: 'Courier New', monospace;
        }
        .table-list {
            list-style: none;
            padding: 0;
        }
        .table-list li {
            padding: 8px 12px;
            background: white;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐳 Test de Conexión MySQL + Docker</h1>
        
        <?php
        // Test de conexión
        $connectionSuccess = false;
        $errorMessage = '';
        $dbInfo = null;
        
        try {
            $connectionSuccess = testDatabaseConnection();
            if ($connectionSuccess) {
                $dbInfo = getDatabaseInfo();
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
        ?>
        
        <!-- Estado de Conexión -->
        <div class="section">
            <h2>
                <span class="icon"><?php echo $connectionSuccess ? '✅' : '❌'; ?></span>
                Estado de Conexión
                <span class="status <?php echo $connectionSuccess ? 'success' : 'error'; ?>">
                    <?php echo $connectionSuccess ? 'CONECTADO' : 'ERROR'; ?>
                </span>
            </h2>
            
            <?php if (!$connectionSuccess): ?>
                <div style="color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin-top: 10px;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                    <br><br>
                    <strong>Soluciones:</strong>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>Verifica que Docker esté corriendo: <code>docker-compose ps</code></li>
                        <li>Reinicia los contenedores: <code>npm run docker:restart</code></li>
                        <li>Revisa los logs: <code>npm run docker:logs</code></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Configuración -->
        <div class="section">
            <h2><span class="icon">⚙️</span> Configuración</h2>
            <div class="info-row">
                <span class="label">Host:</span>
                <span class="value"><?php echo DB_HOST; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Puerto:</span>
                <span class="value"><?php echo DB_PORT; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Base de Datos:</span>
                <span class="value"><?php echo DB_NAME; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Usuario:</span>
                <span class="value"><?php echo DB_USER; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Flow API:</span>
                <span class="value"><?php echo FLOW_API_URL; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Entorno:</span>
                <span class="value"><?php echo getenv('NODE_ENV') ?: 'production'; ?></span>
            </div>
        </div>
        
        <?php if ($connectionSuccess && $dbInfo): ?>
            <!-- Tablas -->
            <div class="section">
                <h2><span class="icon">📊</span> Tablas en la Base de Datos</h2>
                <?php if (empty($dbInfo['tables'])): ?>
                    <p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px;">
                        ⚠️ No se encontraron tablas. El esquema debería cargarse automáticamente al iniciar Docker.
                    </p>
                <?php else: ?>
                    <p style="margin-bottom: 15px;">Se encontraron <strong><?php echo count($dbInfo['tables']); ?> tablas</strong>:</p>
                    <ul class="table-list">
                        <?php foreach ($dbInfo['tables'] as $table): ?>
                            <li><?php echo htmlspecialchars($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- Verificación de Planes -->
            <div class="section">
                <h2><span class="icon">💰</span> Planes Registrados</h2>
                <?php
                try {
                    $pdo = getDatabase();
                    $stmt = $pdo->query("SELECT * FROM plans WHERE is_active = 1");
                    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($plans)): ?>
                        <p style="color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px;">
                            ⚠️ No hay planes registrados en la base de datos.
                        </p>
                    <?php else: ?>
                        <p style="margin-bottom: 15px;">Se encontraron <strong><?php echo count($plans); ?> planes activos</strong>:</p>
                        <ul class="table-list">
                            <?php foreach ($plans as $plan): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($plan['plan_name']); ?></strong>
                                    - $<?php echo number_format($plan['price'], 0, ',', '.'); ?> CLP
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif;
                } catch (Exception $e) {
                    echo '<p style="color: #721c24;">Error al consultar planes: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>🚀 IPStream - Sistema de Radio Online</p>
            <p style="margin-top: 10px;">
                <a href="http://localhost:8080" target="_blank" style="color: #667eea; text-decoration: none;">
                    Abrir phpMyAdmin →
                </a>
            </p>
        </div>
    </div>
</body>
</html>
