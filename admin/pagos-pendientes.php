<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

// Obtener pagos pendientes por transferencia
$stmt = $pdo->query("
    SELECT 
        mp.*,
        o.email,
        o.first_name,
        o.last_name,
        o.project_name,
        o.plan_name
    FROM monthly_payments mp
    JOIN orders o ON mp.order_id = o.id
    WHERE mp.status = 'pending' AND mp.payment_method = 'transfer'
    ORDER BY mp.created_at DESC
");
$pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener pagos confirmados recientemente (últimos 10)
$stmt_confirmed = $pdo->query("
    SELECT 
        mp.*,
        o.email,
        o.first_name,
        o.last_name,
        o.project_name,
        o.plan_name
    FROM monthly_payments mp
    JOIN orders o ON mp.order_id = o.id
    WHERE mp.status = 'paid' AND mp.payment_method = 'transfer'
    ORDER BY mp.paid_at DESC
    LIMIT 10
");
$confirmed_payments = $stmt_confirmed->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="space-y-8">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-gray-800">Pagos por Transferencia</h2>
        <div class="text-sm text-gray-600">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                <?php echo count($pending_payments); ?> Pendientes
            </span>
        </div>
    </div>

    <!-- Pagos Pendientes -->
    <div>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">⏳ Pagos Pendientes de Confirmación</h3>
        
        <?php if (empty($pending_payments)): ?>
            <div class="bg-white shadow rounded-lg p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="mt-4 text-gray-500">No hay pagos pendientes por confirmar</p>
            </div>
        <?php else: ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyecto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Solicitud</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Orden</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pending_payments as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($payment['email']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($payment['project_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($payment['plan_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        $<?php echo number_format($payment['amount'], 0, ',', '.'); ?> CLP
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date("d/m/Y H:i", strtotime($payment['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-mono text-gray-600">
                                        <?php echo htmlspecialchars($payment['commerce_order']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button 
                                        onclick="confirmarPago(<?php echo $payment['id']; ?>, '<?php echo htmlspecialchars($payment['commerce_order']); ?>')"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        ✓ Confirmar Pago
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagos Confirmados Recientemente -->
    <?php if (!empty($confirmed_payments)): ?>
    <div>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">✅ Últimos Pagos Confirmados</h3>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyecto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Confirmación</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($confirmed_payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($payment['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($payment['project_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($payment['plan_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                $<?php echo number_format($payment['amount'], 0, ',', '.'); ?> CLP
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date("d/m/Y H:i", strtotime($payment['paid_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmarPago(paymentId, commerceOrder) {
    if (!confirm('¿Confirmar que se recibió el pago de ' + commerceOrder + '?')) {
        return;
    }
    
    // Mostrar loading
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '⏳ Confirmando...';
    
    fetch('confirmar-pago.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            payment_id: paymentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Pago confirmado exitosamente');
            location.reload();
        } else {
            alert('❌ Error: ' + data.error);
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('❌ Error al confirmar el pago: ' + error);
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>

<?php include 'footer.php'; ?>
