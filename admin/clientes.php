<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];

if ($search_term) {
    $where .= " AND (o.first_name LIKE :search OR o.last_name LIKE :search OR o.email LIKE :search OR o.whatsapp LIKE :search OR o.project_name LIKE :search OR o.commerce_order LIKE :search)";
    $params['search'] = '%' . $search_term . '%';
}
if ($status_filter) {
    $where .= " AND o.status = :status";
    $params['status'] = $status_filter;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders o $where");
$countStmt->execute($params);
$total_orders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, ceil($total_orders / $per_page));

$query = "
    SELECT
        o.*,
        p.plan_name as full_plan_name,
        MAX(mp.paid_at) as last_paid_at,
        COUNT(mp.id) as payment_count,
        SUM(CASE WHEN mp.status = 'paid' THEN mp.amount ELSE 0 END) as total_paid
    FROM
        orders o
    LEFT JOIN
        plans p ON o.plan_id = p.id
    LEFT JOIN
        monthly_payments mp ON o.id = mp.order_id
    $where
    GROUP BY
        o.id
    ORDER BY
        o.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_counts = [];
try {
    $sc = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sc as $row) $status_counts[$row['status']] = $row['cnt'];
} catch (Exception $e) {}

function orderSource($order) {
    if (str_starts_with($order['commerce_order'] ?? '', 'ADM-')) return 'Admin';
    if (str_starts_with($order['commerce_order'] ?? '', 'IPStream-NewSub-Transfer-')) return 'Web (Transferencia)';
    if (str_starts_with($order['commerce_order'] ?? '', 'IPStream-')) return 'Web (Flow)';
    return 'Web';
}
?>
<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between md:items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Clientes</h1>
            <p class="text-gray-600 mt-1">Todos los clientes que han contratado en IPStream</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center space-x-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                <?php echo $total_orders; ?> total
            </span>
            <a href="suscripcion_editar.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                + Nuevo Cliente
            </a>
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
        <form action="clientes.php" method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="text" name="search" placeholder="Buscar por nombre, email, WhatsApp, proyecto u orden..."
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                   value="<?php echo htmlspecialchars($search_term); ?>">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Todos los estados</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Activo</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Fallido</option>
            </select>
            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Buscar</button>
        </form>
    </div>

    <div class="flex flex-wrap gap-2">
        <?php
        $badge_map = [
            'active' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            'failed' => 'bg-red-100 text-red-800',
        ];
        foreach ($status_counts as $st => $cnt):
            $cls = $badge_map[$st] ?? 'bg-gray-100 text-gray-800';
        ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $cls; ?>">
                <?php echo ucfirst($st); ?>: <?php echo $cnt; ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan / Monto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facturación</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contratado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No se encontraron clientes.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($order['first_name'] ?? '?', 0, 1)); ?>
                                </div>
                                <div class="ml-4">
                                    <a href="suscripcion_detalle.php?id=<?php echo $order['id']; ?>" class="text-sm font-semibold text-gray-900 hover:text-indigo-600">
                                        <?php echo htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?>
                                    </a>
                                    <?php if ($order['project_name']): ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($order['project_name']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm"><a href="mailto:<?php echo htmlspecialchars($order['email']); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo htmlspecialchars($order['email']); ?></a></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['whatsapp'] ?? '—'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($order['plan_name'] ?? $order['full_plan_name'] ?? '—'); ?></div>
                            <div class="text-gray-500"><?php echo formatPrice($order['amount']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($order['billing_type'] ?? '—'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div><?php echo date("d/m/Y", strtotime($order['created_at'])); ?></div>
                            <div class="text-xs text-gray-400"><?php echo date("H:i", strtotime($order['created_at'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $st_badges = [
                                'active' => 'bg-green-100 text-green-800',
                                'paid' => 'bg-blue-100 text-blue-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                                'failed' => 'bg-red-100 text-red-800',
                            ];
                            $st_labels = ['active'=>'Activo','paid'=>'Pagado','pending'=>'Pendiente','cancelled'=>'Cancelado','failed'=>'Fallido'];
                            $bc = $st_badges[$order['status']] ?? 'bg-gray-100';
                            ?>
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $bc; ?>">
                                <?php echo $st_labels[$order['status']] ?? $order['status']; ?>
                            </span>
                            <?php if ($order['total_paid'] > 0): ?>
                                <div class="text-xs text-gray-400 mt-1"><?php echo $order['payment_count']; ?> pago<?php echo $order['payment_count'] !== 1 ? 's' : ''; ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500"><?php echo orderSource($order); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="suscripcion_detalle.php?id=<?php echo $order['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Ver detalle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="suscripcion_editar.php?id=<?php echo $order['id']; ?>" class="text-gray-500 hover:text-gray-700" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp'] ?? ''); ?>" target="_blank" class="text-green-600 hover:text-green-800" title="WhatsApp">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                <form method="POST" action="eliminar.php" onsubmit="return confirm('¿Eliminar permanentemente este cliente? También se eliminarán todos sus pagos registrados.')"><?= csrfField() ?>
                                    <input type="hidden" name="type" value="order">
                                    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="redirect" value="clientes.php?<?php echo http_build_query(['search' => $search_term, 'status' => $status_filter, 'page' => $page]); ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Eliminar cliente">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Mostrando <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $total_orders); ?> de <?php echo $total_orders; ?></p>
        <div class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(['search' => $search_term, 'status' => $status_filter, 'page' => $page - 1]); ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Anterior</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?<?php echo http_build_query(['search' => $search_term, 'status' => $status_filter, 'page' => $i]); ?>" class="px-3 py-1 border rounded text-sm <?php echo $i === $page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(['search' => $search_term, 'status' => $status_filter, 'page' => $page + 1]); ?>" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
