<?php
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? null;
    if (!validateCsrfToken($token)) {
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido. Recarga la página e inténtalo de nuevo.']);
            exit;
        }
        die('Error de seguridad: Token CSRF inválido. <a href="javascript:history.back()">Volver</a>');
    }
    // Regenerar token después de uso exitoso
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
