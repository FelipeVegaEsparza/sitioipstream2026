<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

// Obtener todas las categorías
$stmt = $pdo->query("SELECT * FROM tutorial_categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Categorías de Tutoriales</h1>
        <a href="categoria_editar.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Categoría
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tutoriales</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($categories as $category): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tutorials WHERE category_id = ?");
                    $stmt->execute([$category['id']]);
                    $tutorialCount = $stmt->fetch()['count'];
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($category['slug']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>...</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo htmlspecialchars($category['color']); ?>-100 text-<?php echo htmlspecialchars($category['color']); ?>-800">
                                <?php echo htmlspecialchars($category['color']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $category['display_order']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $tutorialCount; ?> tutoriales
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="categoria_editar.php?id=<?php echo $category['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                            <?php if ($tutorialCount == 0): ?>
                                <a href="categoria_eliminar.php?id=<?php echo $category['id']; ?>" 
                                   class="text-red-600 hover:text-red-900"
                                   onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <a href="tutoriales.php" class="text-indigo-600 hover:text-indigo-900">
            ← Volver a Tutoriales
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>
