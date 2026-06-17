<?php
require_once 'auth.php';
require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

$pdo = getDatabase();
$lead_id = (int)($_GET['id'] ?? 0);

if (!$lead_id) {
    header('Location: landing-leads.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM landing_leads WHERE id = ?");
$stmt->execute([$lead_id]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    header('Location: landing-leads.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $new_status = $_POST['new_status'];
        $old_status = $lead['status'];

        if ($new_status === 'converted' && $old_status !== 'converted') {
            $upd = $pdo->prepare("UPDATE landing_leads SET status = ?, trial_started_at = NOW(), last_contacted_at = NOW() WHERE id = ?");
            $upd->execute([$new_status, $lead_id]);
            $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'converted', 'Lead convertido a prueba. Trial de 7 días iniciado.')");
            $logStmt->execute([$lead_id]);
        } elseif ($new_status === 'cancelled') {
            $upd = $pdo->prepare("UPDATE landing_leads SET status = ? WHERE id = ?");
            $upd->execute([$new_status, $lead_id]);
            $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'cancelled', 'Lead cancelado.')");
            $logStmt->execute([$lead_id]);
        } else {
            $upd = $pdo->prepare("UPDATE landing_leads SET status = ?, last_contacted_at = NOW() WHERE id = ?");
            $upd->execute([$new_status, $lead_id]);
            $labels = ['new' => 'Nuevo', 'contacted' => 'Contactado', 'negotiation' => 'Negociación', 'converted' => 'Convertido', 'cancelled' => 'Cancelado'];
            $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'status_change', ?)");
            $logStmt->execute([$lead_id, "Estado cambiado de {$labels[$old_status]} a {$labels[$new_status]}"]);
        }
        header("Location: lead-detail.php?id=$lead_id");
        exit;
    }

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if ($note) {
            $existing = $lead['notes'] ? $lead['notes'] . "\n\n" : '';
            $timestamp = date('d/m/Y H:i');
            $upd = $pdo->prepare("UPDATE landing_leads SET notes = CONCAT(COALESCE(notes, ''), ?) WHERE id = ?");
            $upd->execute(["[$timestamp] $note\n", $lead_id]);
            $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'note', ?)");
            $logStmt->execute([$lead_id, "Nota agregada: $note"]);
        }
        header("Location: lead-detail.php?id=$lead_id");
        exit;
    }

    if ($action === 'send_email') {
        $email_subject = trim($_POST['email_subject'] ?? '');
        $email_body = trim($_POST['email_body'] ?? '');
        if ($email_subject && $email_body) {
            $headers = "From: landing@ipstream.cl\r\nReply-To: landing@ipstream.cl\r\nContent-Type: text/plain; charset=UTF-8";
            $full_body = "$email_body\n\n--\nEquipo IPStream\nhttps://ipstream.cl";
            mail($lead['email'], $email_subject, $full_body, $headers);
            $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'email', ?)");
            $logStmt->execute([$lead_id, "Email enviado: Asunto \"$email_subject\""]);
            $upd = $pdo->prepare("UPDATE landing_leads SET last_contacted_at = NOW() WHERE id = ?");
            $upd->execute([$lead_id]);
        }
        header("Location: lead-detail.php?id=$lead_id");
        exit;
    }

    if ($action === 'log_call') {
        $call_notes = trim($_POST['call_notes'] ?? 'Sin notas');
        $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, 'call', ?)");
        $logStmt->execute([$lead_id, "Llamada registrada: $call_notes"]);
        $upd = $pdo->prepare("UPDATE landing_leads SET last_contacted_at = NOW() WHERE id = ?");
        $upd->execute([$lead_id]);
        header("Location: lead-detail.php?id=$lead_id");
        exit;
    }
}

$logsStmt = $pdo->prepare("SELECT * FROM lead_logs WHERE lead_id = ? ORDER BY created_at DESC");
$logsStmt->execute([$lead_id]);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

$trial_info = null;
if ($lead['status'] === 'converted' && $lead['trial_started_at']) {
    $trial_start = new DateTime($lead['trial_started_at']);
    $trial_end = clone $trial_start;
    $trial_end->modify('+7 days');
    $now = new DateTime();
    $days_left = max(0, (int)$now->diff($trial_end)->format('%r%a'));
    $total_days = $now->diff($trial_start)->days;
    $progress = min(100, ($total_days / 7) * 100);
    $trial_expired = $now > $trial_end;
    $trial_info = compact('trial_start', 'trial_end', 'days_left', 'progress', 'trial_expired');
}

include 'header.php';
?>

<style>
.lead-timeline { position: relative; padding-left: 2rem; }
.lead-timeline::before { content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
.timeline-item { position: relative; padding-bottom: 1.5rem; }
.timeline-item:last-child { padding-bottom: 0; }
.timeline-dot { position: absolute; left: -1.5rem; top: 0.25rem; width: 1rem; height: 1rem; border-radius: 9999px; border: 2px solid white; z-index: 1; }
.trial-bar { height: 8px; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
.trial-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.5s ease; }
.pipeline-step { flex: 1; text-align: center; position: relative; padding: 0.75rem 0.5rem; }
.pipeline-step:not(:last-child)::after { content: ''; position: absolute; top: 50%; right: -0.5rem; width: 1rem; height: 2px; background: #d1d5db; }
.pipeline-step.active .step-icon { background: #6366f1; color: white; box-shadow: 0 0 0 4px rgba(99,102,241,0.2); }
.pipeline-step.done .step-icon { background: #10b981; color: white; }
.pipeline-step .step-icon { width: 2.5rem; height: 2.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-weight: bold; font-size: 0.875rem; background: #f3f4f6; color: #9ca3af; transition: all 0.3s; }
.pipeline-step .step-label { font-size: 0.75rem; font-weight: 600; color: #6b7280; }
.pipeline-step.active .step-label { color: #6366f1; }
.pipeline-step.done .step-label { color: #10b981; }
</style>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="landing-leads.php" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Volver a Leads
            </a>
            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($lead['name']); ?></h1>
        </div>
        <form method="POST" action="eliminar.php" onsubmit="return confirm('¿Eliminar permanentemente este lead? Se perderá todo su historial.')">
            <input type="hidden" name="type" value="lead">
            <input type="hidden" name="id" value="<?php echo $lead['id']; ?>">
            <input type="hidden" name="redirect" value="landing-leads.php">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Eliminar Lead
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Pipeline -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Pipeline</h3>
                <div class="flex items-center justify-between">
                    <?php
                    $steps = [
                        'new' => ['label' => 'Nuevo', 'icon' => '1'],
                        'contacted' => ['label' => 'Contactado', 'icon' => '2'],
                        'negotiation' => ['label' => 'Negociación', 'icon' => '3'],
                        'converted' => ['label' => 'Prueba 7 días', 'icon' => '4'],
                        'cancelled' => ['label' => 'Cancelado', 'icon' => 'X'],
                    ];
                    $current_status = $lead['status'];
                    $found = false;
                    foreach ($steps as $key => $step):
                        if ($key === $current_status) $found = true;
                        $class = '';
                        if ($found && $key !== 'cancelled') $class = 'active';
                        if ($key === 'cancelled' && $current_status === 'cancelled') $class = 'active';
                        if ($key !== 'cancelled' && !$found && $current_status !== 'cancelled') { /* nothing */ }
                        if ($key === 'cancelled' && $current_status !== 'cancelled') continue;
                        ?>
                        <div class="pipeline-step <?php echo $class; ?>">
                            <div class="step-icon"><?php echo $step['icon']; ?></div>
                            <div class="step-label"><?php echo $step['label']; ?></div>
                        </div>
                        <?php if ($key === 'negotiation' && $current_status !== 'cancelled'): ?>
                            <div class="text-gray-300 text-2xl font-light">→</div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="mt-4 pt-4 border-t border-gray-100 flex items-center space-x-3">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="new_status" id="new_status_input" value="">
                    <select id="status_select" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="new" <?php echo $lead['status'] === 'new' ? 'selected' : ''; ?>>Nuevo</option>
                        <option value="contacted" <?php echo $lead['status'] === 'contacted' ? 'selected' : ''; ?>>Contactado</option>
                        <option value="negotiation" <?php echo $lead['status'] === 'negotiation' ? 'selected' : ''; ?>>En Negociación</option>
                        <option value="converted" <?php echo $lead['status'] === 'converted' ? 'selected' : ''; ?>>Convertido (iniciar prueba)</option>
                        <option value="cancelled" <?php echo $lead['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none transition-colors">
                        Actualizar Estado
                    </button>
                </form>
            </div>

            <!-- Trial Countdown -->
            <?php if ($trial_info): ?>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Prueba de 7 Días</h3>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="text-2xl font-bold <?php echo $trial_info['trial_expired'] ? 'text-red-600' : ($trial_info['days_left'] <= 2 ? 'text-yellow-600' : 'text-green-600'); ?>">
                            <?php echo $trial_info['trial_expired'] ? 'Expirado' : $trial_info['days_left'] . ' día' . ($trial_info['days_left'] !== 1 ? 's' : ''); ?>
                        </span>
                        <span class="text-sm text-gray-500 ml-2">restantes</span>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        <div>Inicio: <?php echo $trial_info['trial_start']->format('d/m/Y'); ?></div>
                        <div>Término: <?php echo $trial_info['trial_end']->format('d/m/Y'); ?></div>
                    </div>
                </div>
                <div class="trial-bar">
                    <div class="trial-bar-fill <?php echo $trial_info['trial_expired'] ? 'bg-red-500' : ($trial_info['days_left'] <= 2 ? 'bg-yellow-500' : 'bg-green-500'); ?>" style="width: <?php echo min(100, $trial_info['progress']); ?>%"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Timeline -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Historial de Actividades</h3>
                <?php if (empty($logs)): ?>
                    <p class="text-sm text-gray-400 text-center py-8">Sin actividades registradas.</p>
                <?php else: ?>
                    <div class="lead-timeline">
                        <?php foreach ($logs as $log):
                            $icons = [
                                'created' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
                                'converted' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                'contacted' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
                                'email' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                                'call' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>',
                                'note' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
                                'cancelled' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                'status_change' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
                            ];
                            $colors = [
                                'created' => 'bg-blue-500',
                                'converted' => 'bg-green-500',
                                'contacted' => 'bg-yellow-500',
                                'email' => 'bg-indigo-500',
                                'call' => 'bg-purple-500',
                                'note' => 'bg-gray-500',
                                'cancelled' => 'bg-red-500',
                                'status_change' => 'bg-orange-500',
                            ];
                            $action_labels = [
                                'created' => 'Lead creado',
                                'converted' => 'Convertido a prueba',
                                'contacted' => 'Contacto realizado',
                                'email' => 'Email enviado',
                                'call' => 'Llamada registrada',
                                'note' => 'Nota agregada',
                                'cancelled' => 'Lead cancelado',
                                'status_change' => 'Estado actualizado',
                            ];
                            $color = $colors[$log['action']] ?? 'bg-gray-400';
                            $icon = $icons[$log['action']] ?? '';
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot <?php echo $color; ?> flex items-center justify-center text-white" style="width: 1.5rem; height: 1.5rem; left: -1.75rem;">
                                <?php echo $icon; ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo $action_labels[$log['action']] ?? $log['action']; ?></p>
                                <?php if ($log['description']): ?>
                                    <p class="text-sm text-gray-500 mt-0.5"><?php echo htmlspecialchars($log['description']); ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-1"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Notas Internas</h3>
                <?php if ($lead['notes']): ?>
                    <div class="mb-4 bg-gray-50 rounded-lg p-4 text-sm text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($lead['notes']); ?></div>
                <?php else: ?>
                    <p class="text-sm text-gray-400 mb-4">Sin notas registradas.</p>
                <?php endif; ?>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="add_note">
                    <textarea name="note" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Agregar nota interna..." required></textarea>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gray-600 hover:bg-gray-700 transition-colors">
                        Guardar Nota
                    </button>
                </form>
            </div>

            <!-- Send Email -->
            <div class="bg-white rounded-2xl shadow-sm p-6" id="email-section">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Enviar Correo</h3>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="send_email">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Para</label>
                        <input type="text" readonly value="<?php echo htmlspecialchars($lead['email']); ?>" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Asunto</label>
                        <input type="text" name="email_subject" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Asunto del correo">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mensaje</label>
                        <textarea name="email_body" rows="6" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Escribe tu mensaje aquí..."></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Enviar Correo
                    </button>
                </form>
            </div>

            <!-- Log Call -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Registrar Llamada</h3>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="log_call">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notas de la llamada</label>
                        <textarea name="call_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Resumen de la conversación..."></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Registrar Llamada
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Lead Info Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Información del Lead</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Nombre</label>
                        <p class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($lead['name']); ?></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Email</label>
                        <p class="text-sm mt-1">
                            <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" class="text-indigo-600 hover:text-indigo-800 font-medium"><?php echo htmlspecialchars($lead['email']); ?></a>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">WhatsApp</label>
                        <p class="text-sm mt-1">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['whatsapp']); ?>" target="_blank" class="text-green-600 hover:text-green-800 font-medium">
                                <?php echo htmlspecialchars($lead['whatsapp']); ?>
                                <svg class="w-3 h-3 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </p>
                    </div>
                    <?php if ($lead['project_name']): ?>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Proyecto</label>
                        <p class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($lead['project_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Plan de interés</label>
                        <p class="text-sm font-medium text-gray-900 mt-1"><?php echo $lead['plan_interest'] === 'radio_online' ? 'Radio Online' : 'Radio + TV'; ?></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Recibido</label>
                        <p class="text-sm text-gray-700 mt-1"><?php echo date('d/m/Y H:i', strtotime($lead['created_at'])); ?></p>
                    </div>
                    <?php if ($lead['last_contacted_at']): ?>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Último Contacto</label>
                        <p class="text-sm text-gray-700 mt-1"><?php echo date('d/m/Y H:i', strtotime($lead['last_contacted_at'])); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-xs text-gray-400 uppercase tracking-wider">Estado</label>
                        <p class="mt-1">
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
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo $status_badges[$lead['status']] ?? 'bg-gray-100'; ?>">
                                <?php echo $status_labels[$lead['status']] ?? $lead['status']; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Acciones Rápidas</h3>
                <div class="space-y-3">
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['whatsapp']); ?>?text=<?php echo urlencode("Hola {$lead['name']}! Gracias por solicitar tus 7 días gratis en IPStream. ¿Cómo podemos ayudarte?"); ?>" target="_blank"
                       class="flex items-center justify-between w-full px-4 py-3 bg-green-50 hover:bg-green-100 rounded-xl text-sm font-medium text-green-700 transition-colors">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Enviar WhatsApp
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>?subject=<?php echo urlencode('Tus 7 días gratis en IPStream'); ?>&body=<?php echo urlencode("Hola {$lead['name']},\n\nGracias por solicitar tus 7 días gratis en IPStream. Queremos contarte que ya estamos procesando tu solicitud."); ?>"
                       class="flex items-center justify-between w-full px-4 py-3 bg-indigo-50 hover:bg-indigo-100 rounded-xl text-sm font-medium text-indigo-700 transition-colors">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Enviar Correo
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <button onclick="document.getElementById('email-section').scrollIntoView({behavior:'smooth'})"
                            class="flex items-center justify-between w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 rounded-xl text-sm font-medium text-blue-700 transition-colors text-left">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Redactar Correo Personalizado
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('status_select')?.addEventListener('change', function() {
    document.getElementById('new_status_input').value = this.value;
});
</script>

<?php include 'footer.php'; ?>
