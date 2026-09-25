<?php
require_once 'auth.php';
include 'header.php';

try {
    $pdo = getDatabase();
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'community_radios'");
    if ($tableCheck->rowCount() === 0) {
        $_SESSION['flash_error'] = 'La tabla community_radios no existe. Ejecuta el schema.sql actualizado.';
        $radios = [];
    } else {
        $stmt = $pdo->query("SELECT * FROM community_radios ORDER BY display_order ASC, created_at DESC");
        $radios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error de conexión: ' . $e->getMessage();
    $radios = [];
}
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Comunidad</h1>
            <p class="text-gray-600 mt-1">Publica las radios de tus clientes en la sección Comunidad.</p>
        </div>
        <a href="comunidad-crear.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Añadir Radio
        </a>
    </div>

    <?php if (empty($radios)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">No hay radios publicadas</h3>
            <p class="mt-2 text-gray-500">Agrega la primera radio de tus clientes para mostrarla en la sección Comunidad.</p>
            <a href="comunidad-crear.php" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">Añadir Radio</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($radios as $radio): ?>
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300">
                    <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden flex items-center justify-center p-6">
                        <?php if ($radio['logo_url']): ?>
                            <img src="<?= h($radio['logo_url']) ?>" alt="<?= h($radio['name']) ?>" class="max-w-full max-h-full object-contain">
                        <?php else: ?>
                            <div class="text-gray-400">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-bold text-gray-900"><?= h($radio['name']) ?></h3>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $radio['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $radio['is_active'] ? 'Activo' : 'Inactivo' ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4 line-clamp-3"><?= h($radio['description']) ?></p>
                        <a href="<?= h($radio['site_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 mb-4">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Visitar sitio
                        </a>
                        <div class="flex space-x-2 pt-3 border-t border-gray-100">
                            <a href="comunidad-editar.php?id=<?= $radio['id'] ?>" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Editar</a>
                            <form method="POST" action="eliminar.php" class="inline" onsubmit="return confirm('¿Eliminar esta radio de la comunidad?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="type" value="community_radio">
                                <input type="hidden" name="id" value="<?= $radio['id'] ?>">
                                <input type="hidden" name="redirect" value="comunidad.php">
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium ml-2">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
