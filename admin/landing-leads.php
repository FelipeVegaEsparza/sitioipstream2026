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
    $where .= " AND (name LIKE :search OR email LIKE :search OR whatsapp LIKE :search OR project_name LIKE :search)";
    $params['search'] = '%' . $search_term . '%';
}
if ($status_filter) {
    $where .= " AND status = :status";
    $params['status'] = $status_filter;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM landing_leads $where");
$countStmt->execute($params);
$total_leads = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, ceil($total_leads / $per_page));

$query = "SELECT * FROM landing_leads $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    requireCsrf();
    $lead_id = (int)$_POST['lead_id'];
    $new_status = $_POST['new_status'];
    $old_status = $_POST['old_status'] ?? '';

    $pdo->beginTransaction();
    if ($new_status === 'converted' && $old_status !== 'converted') {
        $upd = $pdo->prepare("UPDATE landing_leads SET status = ?, trial_started_at = NOW() WHERE id = ?");
        $upd->execute([$new_status, $lead_id]);
        $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'converted', 'Lead convertido a prueba. Trial de 7 días iniciado.')");
        $logStmt->execute([$lead_id]);
    } else {
        $upd = $pdo->prepare("UPDATE landing_leads SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $lead_id]);
        $labels = ['new' => 'Nuevo', 'contacted' => 'Contactado', 'negotiation' => 'Negociación', 'converted' => 'Convertido', 'cancelled' => 'Cancelado'];
        $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'status_change', ?)");
        $logStmt->execute([$lead_id, "Estado cambiado de " . ($labels[$old_status] ?? $old_status) . " a " . ($labels[$new_status] ?? $new_status)]);
    }
    $pdo->commit();
    header('Location: landing-leads.php?' . http_build_query(['search' => $search_term, 'status' => $status_filter, 'page' => $page]));
    exit;
}
?>
<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between md:items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Leads Landing</h1>
            <p class="text-gray-600 mt-1">Solicitudes de prueba gratuita recibidas desde la landing promocional</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center space-x-3">
            <?php
            $new_count = 0;
            try {
                $nc = $pdo->query("SELECT COUNT(*) as c FROM landing_leads WHERE status = 'new'")->fetch(PDO::FETCH_ASSOC);
                $new_count = $nc['c'];
            } catch (Exception $e) {}
            ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                <?php echo $total_leads; ?> total
            </span>
            <?php if ($new_count > 0): ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 animate-pulse">
                    <?php echo $new_count; ?> nuevo<?php echo $new_count > 1 ? 's' : ''; ?>
                </span>
            <?php endif; ?>
            <a href="lead-detail.php?id=new" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors opacity-0 pointer-events-none hidden">+</a>
        </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
        <form action="landing-leads.php" method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="text" name="search" placeholder="Buscar por nombre, email, WhatsApp o proyecto..."
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                   value="<?php echo htmlspecialchars($search_term); ?>">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Todos los estados</option>
                <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>Nuevo</option>
                <option value="contacted" <?php echo $status_filter === 'contacted' ? 'selected' : ''; ?>>Contactado</option>
                <option value="negotiation" <?php echo $status_filter === 'negotiation' ? 'selected' : ''; ?>>Negociación</option>
                <option value="converted" <?php echo $status_filter === 'converted' ? 'selected' : ''; ?>>Convertido</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
            </select>
            <button type="submit" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Buscar
            </button>
        </form>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitante</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyecto</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recibido</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado / Trial</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Contacto</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="mt-4 text-sm text-gray-500">No hay solicitudes de prueba todavía.</p>
                            <p class="text-xs text-gray-400">Cuando alguien solicite los 7 días gratis desde la landing, aparecerá aquí.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($leads as $lead):
                    $trial_days_left = null;
                    $trial_expired = false;
                    if ($lead['status'] === 'converted' && $lead['trial_started_at']) {
                        $trial_end = strtotime($lead['trial_started_at'] . ' +7 days');
                        $trial_days_left = max(0, (int)(($trial_end - time()) / 86400));
                        $trial_expired = $trial_days_left === 0 && time() > $trial_end;
                    }
                ?>
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
                            <div class="text-sm text-gray-900">
                                <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo htmlspecialchars($lead['email']); ?></a>
                            </div>
                            <div class="text-sm text-gray-500">
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['whatsapp']); ?>" target="_blank" class="text-green-600 hover:text-green-800"><?php echo htmlspecialchars($lead['whatsapp']); ?></a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($lead['project_name'] ?: '—'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div><?php echo date("d/m/Y", strtotime($lead['created_at'])); ?></div>
                            <div class="text-xs text-gray-400"><?php echo date("H:i", strtotime($lead['created_at'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_classes = [
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
                            $class = $status_classes[$lead['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $class; ?>">
                                <?php echo $status_labels[$lead['status']] ?? $lead['status']; ?>
                            </span>
                            <?php if ($trial_days_left !== null): ?>
                                <div class="mt-1 text-xs <?php echo $trial_expired ? 'text-red-500' : ($trial_days_left <= 2 ? 'text-yellow-600' : 'text-green-600'); ?> font-medium">
                                    <?php if ($trial_expired): ?>
                                        Trial expirado
                                    <?php else: ?>
                                        <?php echo $trial_days_left; ?> día<?php echo $trial_days_left !== 1 ? 's' : ''; ?> restantes
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($lead['last_contacted_at']): ?>
                                <div><?php echo date("d/m/Y", strtotime($lead['last_contacted_at'])); ?></div>
                                <div class="text-xs text-gray-400"><?php echo date("H:i", strtotime($lead['last_contacted_at'])); ?></div>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="lead-detail.php?id=<?php echo $lead['id']; ?>" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium" title="Ver detalle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['whatsapp']); ?>?text=<?php echo urlencode("Hola {$lead['name']}! Gracias por solicitar tus 7 días gratis en IPStream. ¿Cómo podemos ayudarte?"); ?>" target="_blank" class="text-green-600 hover:text-green-800" title="Enviar WhatsApp">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                <form method="POST" class="inline-flex items-center space-x-1" onsubmit="return confirm('¿Actualizar estado?')"><?= csrfField() ?>
                                    <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                    <input type="hidden" name="old_status" value="<?php echo $lead['status']; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="new_status" class="text-xs border border-gray-300 rounded px-2 py-1">
                                        <option value="new" <?php echo $lead['status'] === 'new' ? 'selected' : ''; ?>>Nuevo</option>
                                        <option value="contacted" <?php echo $lead['status'] === 'contacted' ? 'selected' : ''; ?>>Contactado</option>
                                        <option value="negotiation" <?php echo $lead['status'] === 'negotiation' ? 'selected' : ''; ?>>Negociación</option>
                                        <option value="converted" <?php echo $lead['status'] === 'converted' ? 'selected' : ''; ?>>Convertido</option>
                                        <option value="cancelled" <?php echo $lead['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">OK</button>
                                </form>
                                <form method="POST" action="eliminar.php" onsubmit="return confirm('¿Eliminar permanentemente este lead? Se perderá todo su historial.')"><?= csrfField() ?>
                                    <input type="hidden" name="type" value="lead">
                                    <input type="hidden" name="id" value="<?php echo $lead['id']; ?>">
                                    <input type="hidden" name="redirect" value="landing-leads.php?<?php echo http_build_query(['search' => $search_term, 'status' => $status_filter, 'page' => $page]); ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Eliminar lead">
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
        <p class="text-sm text-gray-500">
            Mostrando <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $total_leads); ?> de <?php echo $total_leads; ?> leads
        </p>
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
