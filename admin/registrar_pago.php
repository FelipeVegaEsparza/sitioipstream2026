<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

$subscription_id = $_GET['subscription_id'] ?? null;

if (!$subscription_id) {
    echo "<p class='text-red-500'>No se ha especificado una suscripción para registrar el pago.</p>";
    include 'footer.php';
    exit;
}

// Fetch subscription details to display customer and plan info
$stmt_sub = $pdo->prepare("SELECT o.*, p.plan_name as full_plan_name, p.price as plan_price FROM orders o JOIN plans p ON o.plan_id = p.id WHERE o.id = ?");
$stmt_sub->execute([$subscription_id]);
$subscription = $stmt_sub->fetch(PDO::FETCH_ASSOC);

if (!$subscription) {
    echo "<p class='text-red-500'>Suscripción no encontrada.</p>";
    include 'footer.php';
    exit;
}

function formatPrice($price) {
    return '$' . number_format($price, 0, ',', '.');
}

$default_amount = $subscription['plan_price'] ?? 0;
$default_paid_at = date('Y-m-d H:i');
?>

<div class="space-y-6">
    <div>
        <a href="suscripcion_detalle.php?id=<?php echo htmlspecialchars($subscription['id']); ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">&larr; Volver a Detalles de Suscripción</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Registrar Pago Manual</h1>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
        <form action="process_payment.php" method="POST" class="space-y-6">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($subscription['id']); ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Cliente</label>
                <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['first_name'] . ' ' . $subscription['last_name']); ?></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Plan</label>
                <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['full_plan_name']); ?></p>
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Monto del Pago (CLP)</label>
                <input type="number" name="amount" id="amount" required min="0"
                       value="<?php echo htmlspecialchars($default_amount); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="paid_at" class="block text-sm font-medium text-gray-700">Fecha y Hora del Pago</label>
                <input type="datetime-local" name="paid_at" id="paid_at" required
                       value="<?php echo htmlspecialchars($default_paid_at); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Estado del Pago</label>
                <select name="status" id="status" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="paid" selected>Pagado</option>
                    <option value="pending">Pendiente</option>
                    <option value="failed">Fallido</option>
                </select>
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Registrar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
