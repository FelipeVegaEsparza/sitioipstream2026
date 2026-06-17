<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

// Obtener todas las categorías
$stmt = $pdo->query("SELECT * FROM tutorial_categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los tutoriales con su categoría
$stmt = $pdo->query("
    SELECT t.*, c.name as category_name, c.color as category_color
    FROM tutorials t
    LEFT JOIN tutorial_categories c ON t.category_id = c.id
    ORDER BY c.display_order ASC, t.display_order ASC
");
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar tutoriales por categoría
$tutorialsByCategory = [];
foreach ($tutorials as $tutorial) {
    $catId = $tutorial['category_id'];
    if (!isset($tutorialsByCategory[$catId])) {
        $tutorialsByCategory[$catId] = [];
    }
    $tutorialsByCategory[$catId][] = $tutorial;
}
?>

<div class="space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Tutoriales</h1>
        <div class="space-x-3">
            <a href="tutorial_editar.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Tutorial
            </a>
            <a href="categorias_tutoriales.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Gestionar Categorías
            </a>
        </div>
    </div>

    <?php if (empty($tutorials)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        No hay tutoriales registrados. <a href="tutorial_editar.php" class="font-medium underline">Crea el primero</a>
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <?php if (isset($tutorialsByCategory[$category['id']])): ?>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 bg-<?php echo htmlspecialchars($category['color']); ?>-50 border-b border-<?php echo htmlspecialchars($category['color']); ?>-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                    </div>
                    <div class="border-t border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tutorial</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dificultad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vistas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($tutorialsByCategory[$category['id']] as $tutorial): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-16 w-24">
                                                    <img class="h-16 w-24 rounded object-cover" 
                                                         src="https://img.youtube.com/vi/<?php echo htmlspecialchars($tutorial['youtube_id']); ?>/mqdefault.jpg" 
                                                         alt="Thumbnail">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($tutorial['title']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars(substr($tutorial['description'], 0, 80)) . '...'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($tutorial['duration'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $difficultyColors = [
                                                'Básico' => 'green',
                                                'Intermedio' => 'yellow',
                                                'Avanzado' => 'red'
                                            ];
                                            $color = $difficultyColors[$tutorial['difficulty']] ?? 'gray';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                                <?php echo htmlspecialchars($tutorial['difficulty']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo number_format($tutorial['views']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($tutorial['is_active']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="tutorial_preview.php?id=<?php echo $tutorial['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Vista previa">
                                                <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            <a href="tutorial_editar.php?id=<?php echo $tutorial['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                            <a href="tutorial_eliminar.php?id=<?php echo $tutorial['id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirm('¿Estás seguro de eliminar este tutorial?')">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
