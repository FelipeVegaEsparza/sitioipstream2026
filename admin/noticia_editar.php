<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();
$id = (int)($_GET['id'] ?? 0);
$item = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        $_SESSION['flash_error'] = 'Noticia no encontrada.';
        header('Location: noticias.php');
        exit;
    }
}

$title = $item['title'] ?? '';
$slug = $item['slug'] ?? '';
$excerpt = $item['excerpt'] ?? '';
$content = $item['content'] ?? '';
$image = $item['image'] ?? '';
$author = $item['author'] ?? 'IPStream';
$published_at = $item ? date('Y-m-d\TH:i', strtotime($item['published_at'])) : date('Y-m-d\TH:i');
$is_active = $item['is_active'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $author = trim($_POST['author'] ?? 'IPStream');
    $published_at = $_POST['published_at'] ?? date('Y-m-d\TH:i');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || empty($content)) {
        $_SESSION['flash_error'] = 'El título y el contenido son obligatorios.';
    } else {
        $image_url = $image;

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/news/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $file_name = uniqid('news_') . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_dir . $file_name)) {
                    if ($image_url && strpos($image_url, '/uploads/news/') === 0) {
                        $old_file = __DIR__ . '/..' . $image_url;
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    $image_url = '/uploads/news/' . $file_name;
                }
            }
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', preg_replace('/[^\w\s\-]/', '', $title))));
            $slug = preg_replace('/-+/', '-', $slug);
        }

        $pub_ts = date('Y-m-d H:i:s', strtotime($published_at));

        if ($id) {
            $stmt = $pdo->prepare("UPDATE news SET title=?, slug=?, excerpt=?, content=?, image=?, author=?, published_at=?, is_active=? WHERE id=?");
            $stmt->execute([$title, $slug, $excerpt, $content, $image_url, $author, $pub_ts, $is_active, $id]);
            $_SESSION['flash_success'] = 'Noticia actualizada.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO news (title, slug, excerpt, content, image, author, published_at, is_active) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $slug, $excerpt, $content, $image_url, $author, $pub_ts, $is_active]);
            $_SESSION['flash_success'] = 'Noticia creada.';
        }
        header('Location: noticias.php');
        exit;
    }
}
?>
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6"><?= $id ? 'Editar' : 'Nueva' ?> Noticia</h1>
    <form method="POST" enctype="multipart/form-data" class="max-w-4xl bg-white rounded-xl shadow-lg p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug (dejar vacío para auto-generar)</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($slug) ?>"
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Extracto / Resumen</label>
            <textarea name="excerpt" rows="2" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($excerpt) ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contenido *</label>
            <textarea name="content" rows="15" class="w-full border rounded-lg px-3 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($content) ?></textarea>
            <p class="text-xs text-gray-400 mt-1">Puedes usar HTML.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Imagen</label>
                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP o GIF. Máximo 5 MB.</p>
                <?php if ($image): ?>
                    <div class="mt-2">
                        <p class="text-xs text-gray-500 mb-1">Imagen actual:</p>
                        <img src="<?= htmlspecialchars($image) ?>" alt="Preview" class="w-32 h-20 object-cover rounded border">
                        <label class="inline-flex items-center mt-1 text-xs text-gray-500">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300">
                            <span class="ml-1">Eliminar imagen</span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Autor</label>
                <input type="text" name="author" value="<?= htmlspecialchars($author) ?>"
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de publicación</label>
                <input type="datetime-local" name="published_at" value="<?= htmlspecialchars($published_at) ?>"
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="is_active" id="is_active" value="1" <?= $is_active ? 'checked' : '' ?>
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="is_active" class="ml-2 text-sm text-gray-700">Publicado / Activo</label>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="noticias.php" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Guardar</button>
        </div>
    </form>
</div>
<?php include 'footer.php'; ?>
