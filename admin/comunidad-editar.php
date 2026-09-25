<?php
require_once 'auth.php';

$error = null;
$pdo = null;
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: comunidad.php');
    exit;
}

try {
    $pdo = getDatabase();
} catch (Exception $e) {
    $error = 'Error de conexión a la base de datos: ' . $e->getMessage();
    $pdo = null;
}

$radio = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM community_radios WHERE id = ?");
    $stmt->execute([$id]);
    $radio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$radio) {
        $_SESSION['flash_error'] = 'Radio no encontrada.';
        header('Location: comunidad.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    requireCsrf();
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $site_url = trim($_POST['site_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'El nombre es obligatorio.';
    } elseif (empty($site_url)) {
        $error = 'La URL del sitio es obligatoria.';
    } else {
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $name), '-'));
        }

        $logo_url = uploadImage('logo', 'community', $radio['logo_url']) ?? $radio['logo_url'];

        try {
            $stmt = $pdo->prepare("UPDATE community_radios SET name=?, slug=?, description=?, logo_url=?, site_url=?, display_order=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $logo_url, $site_url, $display_order, $is_active, $id]);
            $_SESSION['flash_success'] = 'Radio actualizada.';
            header('Location: comunidad.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="space-y-6">
    <div>
        <a href="comunidad.php" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Volver a Comunidad</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Editar Radio</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700"><?= h($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($radio): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm p-6 space-y-6">
        <?= csrfField() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Radio *</label>
                <input type="text" name="name" required value="<?= h($_POST['name'] ?? $radio['name']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                <input type="text" name="slug" value="<?= h($_POST['slug'] ?? $radio['slug']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"><?= h($_POST['description'] ?? $radio['description']) ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL del Sitio *</label>
                <input type="url" name="site_url" required value="<?= h($_POST['site_url'] ?? $radio['site_url']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                <input type="number" name="display_order" value="<?= h($_POST['display_order'] ?? $radio['display_order']) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <?php if ($radio['logo_url']): ?>
                    <div class="mb-2">
                        <img src="<?= h($radio['logo_url']) ?>" alt="Actual" class="h-20 w-auto rounded-lg object-contain bg-gray-50 p-2">
                    </div>
                <?php endif; ?>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-1 text-xs text-gray-500">Máximo 2MB. Dejar vacío para mantener el actual.</p>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" <?= $radio['is_active'] ? 'checked' : '' ?> class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Guardar Cambios</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
