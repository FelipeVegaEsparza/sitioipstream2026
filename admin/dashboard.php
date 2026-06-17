<?php
require_once 'auth.php';
include 'header.php';

try {
    $pdo = getDatabase();
} catch (Exception $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

try {
    // Fetch statistics
    $total_subs_stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'active'");
    $total_subscriptions = $total_subs_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_revenue_stmt = $pdo->query("SELECT SUM(amount) as total FROM monthly_payments WHERE status = 'paid' AND MONTH(paid_at) = MONTH(CURRENT_DATE()) AND YEAR(paid_at) = YEAR(CURRENT_DATE())");
    $monthly_revenue = $total_revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $pending_transfers_stmt = $pdo->query("SELECT COUNT(*) as total FROM monthly_payments WHERE status = 'pending'");
    $pending_transfers = $pending_transfers_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $total_payments_stmt = $pdo->query("SELECT COUNT(*) as total FROM monthly_payments WHERE status = 'paid' AND MONTH(paid_at) = MONTH(CURRENT_DATE()) AND YEAR(paid_at) = YEAR(CURRENT_DATE())");
    $monthly_payments_count = $total_payments_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch recent monthly payments
    $payments_stmt = $pdo->query("SELECT mp.*, o.email, o.first_name, o.last_name FROM monthly_payments mp JOIN orders o ON mp.order_id = o.id ORDER BY mp.created_at DESC LIMIT 10");
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>";
    echo "<strong>Error al cargar estadísticas:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    // Set default values
    $total_subscriptions = 0;
    $monthly_revenue = 0;
    $pending_transfers = 0;
    $monthly_payments_count = 0;
    $payments = [];
}

try {
    // --- NEW: Fetch overdue subscriptions --- 
    $overdue_subscriptions = [];
    $stmt_active_subs = $pdo->query("
        SELECT
            o.id,
            o.first_name,
            o.last_name,
            o.email,
            o.plan_name,
            o.billing_type,
            MAX(mp.paid_at) AS last_paid_at
        FROM
            orders o
        LEFT JOIN
            monthly_payments mp ON o.id = mp.order_id AND mp.status = 'paid'
        WHERE
            o.status = 'active'
        GROUP BY
            o.id, o.first_name, o.last_name, o.email, o.plan_name, o.billing_type
    ");

    $active_subscriptions_with_last_payment = $stmt_active_subs->fetchAll(PDO::FETCH_ASSOC);

    $current_date = new DateTime();

    foreach ($active_subscriptions_with_last_payment as $sub) {
        $last_paid_at = $sub['last_paid_at'] ? new DateTime($sub['last_paid_at']) : null;

        // If no payment recorded, consider it overdue from order creation date
        if (!$last_paid_at) {
            // This case means an active subscription has no recorded paid monthly_payments.
            // It implies it's overdue from the start or the first payment wasn't logged in monthly_payments.
            // For now, we'll mark it as overdue.
            $overdue_subscriptions[] = $sub;
            continue;
        }

        $due_date = clone $last_paid_at;
        if ($sub['billing_type'] === 'monthly') {
            $due_date->modify('+1 month');
        } elseif ($sub['billing_type'] === 'annual') {
            $due_date->modify('+1 year');
        }

        if ($current_date > $due_date) {
            $overdue_subscriptions[] = $sub;
        }
    }
} catch (Exception $e) {
    echo "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4'>";
    echo "<strong>Advertencia:</strong> No se pudieron cargar las suscripciones vencidas: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    $overdue_subscriptions = [];
}

// --- END NEW --- 

?>

<!-- Welcome Section -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">Bienvenido al Dashboard</h2>
    <p class="text-gray-600">Resumen general de tu plataforma IPStream</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-6 mb-8">
    <!-- Total Subscriptions -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $total_subscriptions; ?></h3>
        <p class="text-blue-100 text-sm">Suscripciones Activas</p>
    </div>

    <!-- Monthly Revenue -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1">$<?php echo number_format($monthly_revenue, 0, ',', '.'); ?></h3>
        <p class="text-green-100 text-sm">Ingresos del Mes</p>
    </div>

    <!-- Pending Transfers -->
    <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $pending_transfers; ?></h3>
        <p class="text-yellow-100 text-sm">Transferencias Pendientes</p>
    </div>

    <!-- Monthly Payments -->
    <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $monthly_payments_count; ?></h3>
        <p class="text-purple-100 text-sm">Pagos Este Mes</p>
    </div>

    <!-- Total Customers -->
    <?php
    try {
        $total_customers_stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
        $total_customers = $total_customers_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $total_customers = 0;
    }
    ?>
    <a href="clientes.php" class="bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 block">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $total_customers; ?></h3>
        <p class="text-indigo-100 text-sm">Total Clientes</p>
    </a>

    <!-- Landing Leads -->
    <?php
    try {
        $new_leads_stmt = $pdo->query("SELECT COUNT(*) as total FROM landing_leads WHERE status = 'new'");
        $new_leads = $new_leads_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $new_leads = 0;
    }
    ?>
    <a href="landing-leads.php" class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 block">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <?php if ($new_leads > 0): ?>
                <span class="bg-white text-rose-600 text-xs font-bold px-2 py-1 rounded-full animate-pulse"><?php echo $new_leads; ?> nuevo<?php echo $new_leads > 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $new_leads; ?></h3>
        <p class="text-pink-100 text-sm">Leads Nuevos (Landing)</p>
    </a>

    <!-- Active Trials -->
    <?php
    try {
        $trials_stmt = $pdo->query("SELECT COUNT(*) as total FROM landing_leads WHERE status = 'converted' AND (trial_started_at IS NULL OR trial_started_at >= NOW() - INTERVAL 7 DAY)");
        $active_trials = $trials_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $active_trials = 0;
    }
    ?>
    <a href="landing-leads.php?status=converted" class="bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 block">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <?php
            try {
                $expiring_stmt = $pdo->query("SELECT COUNT(*) as total FROM landing_leads WHERE status = 'converted' AND trial_started_at IS NOT NULL AND trial_started_at <= NOW() - INTERVAL 5 DAY AND trial_started_at > NOW() - INTERVAL 7 DAY");
                $expiring_trials = $expiring_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                if ($expiring_trials > 0): ?>
                    <span class="bg-white text-emerald-600 text-xs font-bold px-2 py-1 rounded-full animate-pulse"><?php echo $expiring_trials; ?> por vencer</span>
                <?php endif;
            } catch (Exception $e) {}
            ?>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $active_trials; ?></h3>
        <p class="text-teal-100 text-sm">Pruebas Activas (7 días)</p>
    </a>

    <!-- News -->
    <?php
    try {
        $news_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM news WHERE is_active = 1");
        $news_count = $news_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $news_count = 0;
    }
    ?>
    <a href="noticias.php" class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300 block">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-white bg-opacity-20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-3xl font-bold mb-1"><?php echo $news_count; ?></h3>
        <p class="text-amber-100 text-sm">Noticias Publicadas</p>
    </a>
</div>

<div class="space-y-8">
    <!-- Overdue Subscriptions Section -->
    <?php if (!empty($overdue_subscriptions)): ?>
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11.62 7.75a.75.75 0 011.5 0v4.5a.75.75 0 01-1.5 0v-4.5zm-1.5 6a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">¡Atención! Suscripciones con Pagos Atrasados</h3>
            </div>
        </div>
    </div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Pago</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($overdue_subscriptions as $sub): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?><br>
                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($sub['email']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($sub['plan_name']); ?> (<?php echo htmlspecialchars(ucfirst($sub['billing_type'])); ?>)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $sub['last_paid_at'] ? date("d/m/Y", strtotime($sub['last_paid_at'])) : 'Nunca'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="suscripcion_detalle.php?id=<?php echo $sub['id']; ?>" class="text-indigo-600 hover:text-indigo-900">Ver Detalles</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Recent Leads -->
    <?php
    try {
        $recent_leads_stmt = $pdo->query("SELECT * FROM landing_leads ORDER BY created_at DESC LIMIT 5");
        $recent_leads = $recent_leads_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recent_leads = [];
    }
    ?>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-pink-50 to-rose-50">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <svg class="w-6 h-6 mr-3 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                Últimos Leads Recibidos
                <a href="landing-leads.php" class="ml-auto text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver todos →</a>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contacto</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Recibido</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_leads)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-4 text-sm text-gray-500">No hay leads recibidos todavía.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($recent_leads as $lead): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($lead['name'], 0, 1)); ?>
                                    </div>
                                    <div class="ml-4">
                                        <a href="lead-detail.php?id=<?php echo $lead['id']; ?>" class="text-sm font-semibold text-gray-900 hover:text-indigo-600"><?php echo htmlspecialchars($lead['name']); ?></a>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($lead['plan_interest'] === 'radio_online' ? 'Radio Online' : 'Radio + TV'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($lead['email']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($lead['whatsapp']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><?php echo date("d/m/Y", strtotime($lead['created_at'])); ?></div>
                                <div class="text-xs text-gray-400"><?php echo date("H:i", strtotime($lead['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_badges = [
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'contacted' => 'bg-yellow-100 text-yellow-800',
                                    'negotiation' => 'bg-purple-100 text-purple-800',
                                    'converted' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $status_labels = [
                                    'new' => 'Nuevo',
                                    'contacted' => 'Contactado',
                                    'negotiation' => 'Negociación',
                                    'converted' => 'Convertido',
                                    'cancelled' => 'Cancelado',
                                ];
                                ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_badges[$lead['status']] ?? 'bg-gray-100'; ?>">
                                    <?php echo $status_labels[$lead['status']] ?? $lead['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Monthly Payments -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-green-50 to-blue-50">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Últimos Pagos Mensuales Recibidos
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cliente</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Monto</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-4 text-sm text-gray-500">No se han registrado pagos mensuales todavía.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($payment['first_name'], 0, 1)); ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($payment['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-green-600">$<?php echo number_format($payment['amount'], 0, ',', '.'); ?></div>
                                <div class="text-xs text-gray-500">CLP</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date("d/m/Y", strtotime($payment['paid_at'] ?? $payment['created_at'])); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date("H:i", strtotime($payment['paid_at'] ?? $payment['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($payment['status'] === 'paid'): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Pagado
                                    </span>
                                <?php elseif ($payment['status'] === 'pending'): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        Pendiente
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Fallido
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>