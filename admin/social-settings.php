<?php
require_once 'auth.php';
require_once __DIR__ . '/../php/config/settings.php';
include 'header.php';

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    try {
        $pdo = getDatabase();
        $keys = ['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube', 'social_tiktok'];
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($keys as $key) {
            $value = trim($_POST[$key] ?? '');
            $stmt->execute([$key, $value]);
        }
        $saved = true;
    } catch (Exception $e) {
        $error = 'Error al guardar: ' . $e->getMessage();
    }
}

$social = getSettings();
?>

<div class="p-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Redes Sociales</h1>
        <p class="text-gray-600 mt-1">Configura los enlaces a las redes sociales de IPStream que aparecen en el sitio.</p>
    </div>

    <?php if ($saved): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700">Redes sociales actualizadas correctamente.</p>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700"><?= h($error) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="max-w-2xl bg-white rounded-2xl shadow-sm p-6 space-y-6">
        <?= csrfField() ?>
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                <input type="url" name="social_facebook" value="<?= h($social['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/ipstream" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Twitter / X</label>
                <input type="url" name="social_twitter" value="<?= h($social['social_twitter'] ?? '') ?>" placeholder="https://x.com/ipstream" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                <input type="url" name="social_instagram" value="<?= h($social['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/ipstream" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">YouTube</label>
                <input type="url" name="social_youtube" value="<?= h($social['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/@ipstream" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">TikTok</label>
                <input type="url" name="social_tiktok" value="<?= h($social['social_tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@ipstream" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
        <div class="flex justify-end pt-4 border-t border-gray-200">
            <button type="submit" class="px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Guardar Cambios</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
