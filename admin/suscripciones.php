<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

// Search logic
$search_term = $_GET['search'] ?? '';
$query = "
    SELECT
        o.id,
        o.first_name,
        o.last_name,
        o.email,
        o.plan_name,
        o.billing_type,
        o.amount, -- Amount at the time of order
        p.price AS current_plan_price, -- Current price from plans table
        MAX(mp.paid_at) AS last_paid_at,
        o.created_at
    FROM
        orders o
    JOIN
        plans p ON o.plan_id = p.id
    LEFT JOIN
        monthly_payments mp ON o.id = mp.order_id AND mp.status = 'paid'
    WHERE
        o.status = 'active'
";

$params = [];
if ($search_term) {
    $query .= " AND (o.first_name LIKE :search OR o.last_name LIKE :search OR o.email LIKE :search OR o.project_name LIKE :search)";
    $params['search'] = '%' . $search_term . '%';
}

$query .= "
    GROUP BY
        o.id, o.first_name, o.last_name, o.email, o.plan_name, o.billing_type, o.amount, p.price, o.created_at
    ORDER BY
        o.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between md:items-center">
        <h1 class="text-3xl font-bold text-gray-900">Suscripciones Activas</h1>
        <a href="suscripcion_editar.php" class="mt-4 md:mt-0 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Añadir Nueva Suscripción
        </a>
    </div>

    <!-- Search Form -->
    <div class="bg-white p-4 rounded-lg shadow">
        <form action="suscripciones.php" method="GET">
            <div class="flex">
                <input type="text" name="search" placeholder="Buscar por nombre, email o proyecto..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-l-md focus:ring-indigo-500 focus:border-indigo-500"
                       value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-r-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Subscriptions Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Días para Próximo Pago</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Acciones</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No se encontraron suscripciones activas.</td>
                    </tr>
                <?php endif; ?>
                <?php
                $current_date = new DateTime();
                foreach ($subscriptions as $sub):
                    $last_paid_at = $sub['last_paid_at'] ? new DateTime($sub['last_paid_at']) : new DateTime($sub['created_at']); // Fallback to created_at if no payments
                    $next_due_date = clone $last_paid_at;

                    if ($sub['billing_type'] === 'monthly') {
                        $next_due_date->modify('+1 month');
                    } elseif ($sub['billing_type'] === 'annual') {
                        $next_due_date->modify('+1 year');
                    }

                    // If next_due_date is in the past, keep advancing it until it's in the future
                    while ($next_due_date < $current_date) {
                        if ($sub['billing_type'] === 'monthly') {
                            $next_due_date->modify('+1 month');
                        } elseif ($sub['billing_type'] === 'annual') {
                            $next_due_date->modify('+1 year');
                        }
                    }

                    $days_remaining = $current_date->diff($next_due_date)->days;
                    if ($current_date > $next_due_date) { // If it's actually overdue, show 0 or negative days
                        $days_remaining = -$current_date->diff($next_due_date)->days; // This will be negative for overdue
                    }
                ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sub['email']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($sub['plan_name']); ?> (<?php echo htmlspecialchars(ucfirst($sub['billing_type'])); ?>)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatPrice($sub['current_plan_price']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php
                            if ($days_remaining <= 0) {
                                echo '<span class="text-red-600 font-semibold">Atrasado</span>';
                            } else {
                                echo $days_remaining . ' días';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="suscripcion_detalle.php?id=<?php echo $sub['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Ver Detalles</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
