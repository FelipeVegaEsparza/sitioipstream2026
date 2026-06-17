<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

$subscription_id = $_GET['id'] ?? null;

if (!$subscription_id) {
    echo "<p class='text-red-500'>No se ha especificado una suscripción.</p>";
    include 'footer.php';
    exit;
}

// Fetch subscription details
$stmt_sub = $pdo->prepare("SELECT o.*, p.plan_name as full_plan_name, p.price as plan_price FROM orders o JOIN plans p ON o.plan_id = p.id WHERE o.id = ?");
$stmt_sub->execute([$subscription_id]);
$subscription = $stmt_sub->fetch(PDO::FETCH_ASSOC);

if (!$subscription) {
    echo "<p class='text-red-500'>Suscripción no encontrada.</p>";
    include 'footer.php';
    exit;
}

// Fetch associated payments
$stmt_payments = $pdo->prepare("SELECT * FROM monthly_payments WHERE order_id = ? ORDER BY created_at DESC");
$stmt_payments->execute([$subscription_id]);
$payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    switch ($status) {
        case 'active':
        case 'paid':
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>';
        case 'pending':
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>';
        case 'cancelled':
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Cancelado</span>';
        case 'failed':
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Fallido</span>';
        default:
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">' . htmlspecialchars($status) . '</span>';
    }
}

function formatPrice($price) {
    return '$' . number_format($price, 0, ',', '.');
}
?>

<div class="space-y-6">
    <div>
        <a href="suscripciones.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">&larr; Volver a Suscripciones</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Detalles de Suscripción</h1>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Suscripción #<?php echo htmlspecialchars($subscription['id']); ?></h2>
            <div class="space-x-2">
                <a href="suscripcion_editar.php?id=<?php echo htmlspecialchars($subscription['id']); ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Editar Suscripción
                </a>
                <a href="registrar_pago.php?subscription_id=<?php echo htmlspecialchars($subscription['id']); ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Registrar Pago
                </a>
                <form method="POST" action="eliminar.php" class="inline" onsubmit="return confirm('¿Eliminar permanentemente este cliente? También se eliminarán todos sus pagos registrados.')">
                    <input type="hidden" name="type" value="order">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($subscription['id']); ?>">
                    <input type="hidden" name="redirect" value="suscripciones.php">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md shadow-sm text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Cliente</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($subscription['first_name'] . ' ' . $subscription['last_name']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($subscription['email']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">WhatsApp</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($subscription['whatsapp']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Nombre del Proyecto</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($subscription['project_name']); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Plan</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($subscription['full_plan_name']); ?> (<?php echo formatPrice($subscription['plan_price']); ?>)</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Tipo de Facturación</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars(ucfirst($subscription['billing_type'])); ?></dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php echo getStatusBadge($subscription['status']); ?>
                    </dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo date("d/m/Y H:i", strtotime($subscription['created_at'])); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Payments History -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <h2 class="text-2xl font-semibold text-gray-800 p-6">Historial de Pagos</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID de Pago</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Pago</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No hay pagos registrados para esta suscripción.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatPrice($payment['amount']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo getStatusBadge($payment['status']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date("d/m/Y H:i", strtotime($payment['paid_at'] ?? $payment['created_at'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
