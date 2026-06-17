<?php
require_once 'auth.php';

requireCsrf();
$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    $_SESSION['flash_error'] = 'Solicitud inválida.';
    header('Location: noticias.php');
    exit;
}

try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT title FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) throw new Exception('Noticia no encontrada.');

    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    $_SESSION['flash_success'] = "Noticia \"{$item['title']}\" eliminada.";
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
}

header('Location: noticias.php');
exit;
