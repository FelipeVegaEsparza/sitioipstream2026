<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

$subscription_id = $_GET['id'] ?? null;
$subscription = null;
$plans = [];

// Fetch plans for dropdown
$plans_stmt = $pdo->query("SELECT id, plan_name FROM plans ORDER BY plan_name");
$plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($subscription_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$subscription_id]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        echo "<p class='text-red-500'>Suscripción no encontrada.</p>";
        include 'footer.php';
        exit;
    }
}

$page_title = $subscription_id ? "Editar Suscripción" : "Añadir Nueva Suscripción";
?>

<div class="space-y-6">
    <div>
        <a href="suscripciones.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">&larr; Volver a Suscripciones</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2"><?php echo $page_title; ?></h1>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
        <form action="update_suscripcion.php" method="POST" class="space-y-6"><?= csrfField() ?>
            <?php if ($subscription_id): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($subscription['id']); ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="first_name" id="first_name" required
                           value="<?php echo htmlspecialchars($subscription['first_name'] ?? ''); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Apellido</label>
                    <input type="text" name="last_name" id="last_name" required
                           value="<?php echo htmlspecialchars($subscription['last_name'] ?? ''); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" required
                       value="<?php echo htmlspecialchars($subscription['email'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp</label>
                <input type="text" name="whatsapp" id="whatsapp" required
                       value="<?php echo htmlspecialchars($subscription['whatsapp'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="project_name" class="block text-sm font-medium text-gray-700">Nombre del Proyecto</label>
                <input type="text" name="project_name" id="project_name" required
                       value="<?php echo htmlspecialchars($subscription['project_name'] ?? ''); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="plan_id" class="block text-sm font-medium text-gray-700">Plan</label>
                <select name="plan_id" id="plan_id" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo htmlspecialchars($plan['id']); ?>"
                            <?php echo (isset($subscription['plan_id']) && $subscription['plan_id'] == $plan['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plan['plan_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo de Facturación</label>
                <div class="mt-1 space-y-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="billing_type" value="monthly" required
                               class="form-radio h-4 w-4 text-indigo-600"
                               <?php echo (isset($subscription['billing_type']) && $subscription['billing_type'] == 'monthly') ? 'checked' : ''; ?>
                               <?php echo (!isset($subscription['billing_type']) || $subscription['billing_type'] == '') ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-700">Mensual</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="billing_type" value="annual" required
                               class="form-radio h-4 w-4 text-indigo-600"
                               <?php echo (isset($subscription['billing_type']) && $subscription['billing_type'] == 'annual') ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-700">Anual</span>
                    </label>
                </div>
            </div>

            <?php if ($subscription_id): // Only show status for existing subscriptions ?>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <?php
                        $statuses = ['active', 'pending', 'cancelled', 'failed', 'paid'];
                        foreach ($statuses as $s):
                        ?>
                            <option value="<?php echo htmlspecialchars($s); ?>"
                                <?php echo (isset($subscription['status']) && $subscription['status'] == $s) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($s); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <?php echo $subscription_id ? "Actualizar Suscripción" : "Crear Suscripción"; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
