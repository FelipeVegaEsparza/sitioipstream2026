<?php
function formatPrice(int $amount): string {
    return '$' . number_format($amount, 0, ',', '.');
}

function getStatusBadge(string $status): string {
    $map = [
        'pending'     => ['bg-yellow-100 text-yellow-800', 'Pendiente'],
        'paid'        => ['bg-green-100 text-green-800', 'Pagado'],
        'active'      => ['bg-blue-100 text-blue-800', 'Activo'],
        'cancelled'   => ['bg-red-100 text-red-800', 'Cancelado'],
        'failed'      => ['bg-red-100 text-red-800', 'Fallido'],
        'new'         => ['bg-blue-100 text-blue-800', 'Nuevo'],
        'contacted'   => ['bg-yellow-100 text-yellow-800', 'Contactado'],
        'negotiation' => ['bg-purple-100 text-purple-800', 'Negociación'],
        'converted'   => ['bg-green-100 text-green-800', 'Convertido'],
    ];
    $classes = $map[$status] ?? ['bg-gray-100 text-gray-800', $status];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $classes[0] . '">' . htmlspecialchars($classes[1]) . '</span>';
}

function formatDate(string $date): string {
    if (empty($date) || $date === '0000-00-00 00:00:00') return '—';
    $ts = strtotime($date);
    return date('d/m/Y H:i', $ts);
}

function h(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function methodNotAllowed(): never {
    http_response_code(405);
    die('Método no permitido');
}

const MAX_UPLOAD_SIZE = 2 * 1024 * 1024;

const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

function uploadImage(string $fieldName, string $subdir, ?string $oldUrl = null): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $_SESSION['flash_error'] = 'La imagen supera el tamaño máximo de 2MB.';
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        $_SESSION['flash_error'] = 'Tipo de archivo no permitido. Solo imágenes JPG, PNG, WebP y GIF.';
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };
        if (!$ext) {
            $_SESSION['flash_error'] = 'No se pudo determinar la extensión del archivo.';
            return null;
        }
    }

    $uploadDir = __DIR__ . '/../../uploads/' . $subdir . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid($subdir . '_') . '.' . $ext;
    $destPath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $_SESSION['flash_error'] = 'Error al guardar la imagen.';
        return null;
    }

    // Eliminar imagen anterior
    if ($oldUrl && str_starts_with($oldUrl, '/uploads/' . $subdir . '/')) {
        $oldPath = __DIR__ . '/../..' . $oldUrl;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    return '/uploads/' . $subdir . '/' . $fileName;
}
