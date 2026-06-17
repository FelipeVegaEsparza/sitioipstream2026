<?php
// Panel de administración
// Archivo: admin/orders.php

require_once '../config/config.php';
require_once '../config/database.php';

try {
    $pdo = getDatabase();
    
    // Obtener órdenes
    $stmt = $pdo->query("
        SELECT * FROM orders 
        ORDER BY created_at DESC 
        LIMIT 100
    ");
    $orders = $stmt->fetchAll();
    
    // Contar por estado
    $statusCounts = [
        'active' => 0,
        'pending' => 0,
        'paid' => 0,
        'failed' => 0
    ];
    
    foreach ($orders as $order) {
        if (isset($statusCounts[$order['status']])) {
            $statusCounts[$order['status']]++;
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $orders = [];
}

function formatPrice($amount) {
    return '$' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    if (!$date) return 'N/A';
    return date('d-m-Y H:i', strtotime($date));
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'paid' => 'bg-blue-100 text-blue-800',
        'active' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'failed' => 'bg-gray-100 text-gray-800'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - IPStream</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Panel de Administración</h1>
                <p class="text-gray-600 mt-2">Gestión de órdenes y suscripciones</p>
            </div>
            <button onclick="window.location.reload()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Actualizar
            </button>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Error de conexión</h3>
                        <p class="text-sm text-red-700 mt-1"><?= htmlspecialchars($error) ?></p>
                        <p class="text-sm text-red-700 mt-1">Verifica la configuración de MySQL en config/config.php</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-medium text-gray-900">
                            Órdenes Recientes (<?= count($orders) ?>)
                        </h2>
                        <div class="flex space-x-4 text-sm">
                            <div class="text-green-600">
                                ✅ Activas: <?= $statusCounts['active'] ?>
                            </div>
                            <div class="text-yellow-600">
                                ⏳ Pendientes: <?= $statusCounts['pending'] ?>
                            </div>
                            <div class="text-blue-600">
                                💰 Pagadas: <?= $statusCounts['paid'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay órdenes</h3>
                        <p class="mt-1 text-sm text-gray-500">Cuando los clientes realicen pagos, aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyecto/Radio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= htmlspecialchars($order['email']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($order['project_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= $order['plan_type'] === 'radio' ? '📻 Radio' : '📺 Radio + TV' ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['whatsapp']) ?>" 
                                                   target="_blank" 
                                                   class="text-green-600 hover:text-green-800 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                                    </svg>
                                                    <?= htmlspecialchars($order['whatsapp']) ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($order['plan_name']) ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?= $order['billing_type'] === 'annual' ? '📅 Anual' : '📅 Mensual' ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= formatPrice($order['amount']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= getStatusColor($order['status']) ?>">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div><?= formatDate($order['created_at']) ?></div>
                                            <?php if ($order['paid_at']): ?>
                                                <div class="text-xs text-green-600">
                                                    Pagado: <?= formatDate($order['paid_at']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($order['activated_at']): ?>
                                                <div class="text-xs text-blue-600">
                                                    Activado: <?= formatDate($order['activated_at']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-xs font-mono text-gray-500">
                                                <?= htmlspecialchars($order['commerce_order']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-800">Información</h3>
                    <p class="text-sm text-blue-700 mt-1">
                        Este panel muestra las órdenes almacenadas en MySQL. En producción, agrega autenticación para proteger esta página.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>