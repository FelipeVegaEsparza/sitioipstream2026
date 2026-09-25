<?php
require_once 'auth.php';

$error = null;
$pdo = null;

try {
    $pdo = getDatabase();
} catch (Exception $e) {
    $error = 'Error de conexión a la base de datos: ' . $e->getMessage();
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

        $logo_url = uploadImage('logo', 'community');

        try {
            $stmt = $pdo->prepare("INSERT INTO community_radios (name, slug, description, logo_url, site_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $logo_url, $site_url, $display_order, $is_active]);
            $_SESSION['flash_success'] = 'Radio agregada a la comunidad.';
            header('Location: comunidad.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="space-y-6">
    <div>
        <a href="comunidad.php" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Volver a Comunidad</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Añadir Radio a la Comunidad</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700"><?= h($error) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm p-6 space-y-6">
        <?= csrfField() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Radio *</label>
                <input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug (URL amigable)</label>
                <input type="text" name="slug" value="<?= h($_POST['slug'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Se genera automáticamente">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"><?= h($_POST['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL del Sitio *</label>
                <input type="url" name="site_url" required value="<?= h($_POST['site_url'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://ejemplo.cl">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                <input type="number" name="display_order" value="<?= h($_POST['display_order'] ?? '0') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <p class="mt-1 text-xs text-gray-500">Máximo 2MB. Formatos: JPG, PNG, WebP</p>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" checked class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Guardar</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
