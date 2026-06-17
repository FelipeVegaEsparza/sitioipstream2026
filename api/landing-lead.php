<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../php/config/config.php';
require_once __DIR__ . '/../php/config/database.php';

try {
    $pdo = getDatabase();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $project_name = trim($_POST['project_name'] ?? '');
    $plan_interest = trim($_POST['plan_interest'] ?? 'radio_online');

    $errors = [];

    if (empty($name)) $errors[] = 'El nombre es obligatorio';
    if (empty($email)) $errors[] = 'El email es obligatorio';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido';
    if (empty($whatsapp)) $errors[] = 'El WhatsApp es obligatorio';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO landing_leads (name, email, whatsapp, project_name, plan_interest, status)
        VALUES (:name, :email, :whatsapp, :project_name, :plan_interest, 'new')
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':whatsapp' => $whatsapp,
        ':project_name' => $project_name,
        ':plan_interest' => $plan_interest,
    ]);

    $lead_id = $pdo->lastInsertId();

    $logStmt = $pdo->prepare("INSERT INTO lead_logs (lead_id, action, description) VALUES (?, ?, ?)");
    $logStmt->execute([$lead_id, 'created', "Lead creado desde landing. Plan: $plan_interest"]);

    $leadInfo = "Nombre: $name\nEmail: $email\nWhatsApp: $whatsapp\nProyecto: $project_name\nPlan: $plan_interest\n\nRecibido: " . date('d/m/Y H:i');
    $subject = "Nuevo lead 7 días gratis - IPStream";
    $headers = "From: landing@ipstream.cl\r\nReply-To: $email";
    @mail(ADMIN_EMAIL, $subject, $leadInfo, $headers);

    echo json_encode([
        'success' => true,
        'message' => '¡Solicitud recibida! Te contactaremos en las próximas horas para activar tus 7 días gratis.'
    ]);

} catch (Exception $e) {
    error_log('Landing lead error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar tu solicitud. Intenta nuevamente.']);
}
