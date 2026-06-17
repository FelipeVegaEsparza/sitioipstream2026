<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

// Obtener todas las categorías
$stmt = $pdo->query("SELECT * FROM plan_categories ORDER BY display_order ASC, name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar planes por categoría
$stmt_counts = $pdo->query("
    SELECT category_id, COUNT(*) as total 
    FROM plans 
    GROUP BY category_id
");
$plan_counts = [];
while ($row = $stmt_counts->fetch(PDO::FETCH_ASSOC)) {
    $plan_counts[$row['category_id']] = $row['total'];
}
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Categorías de Planes</h2>
            <p class="text-gray-600">Gestiona las categorías para organizar tus planes</p>
        </div>
        <a href="categoria_crear.php" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-semibold shadow-lg transform hover:scale-105 transition-all duration-300 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nueva Categoría
        </a>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($categories as $category): 
            $plan_count = $plan_counts[$category['id']] ?? 0;
        ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center text-2xl mr-3">
                            <?php echo htmlspecialchars($category['icon'] ?: '📁'); ?>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($category['slug']); ?></p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $category['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                        <?php echo $category['is_active'] ? 'Activa' : 'Inactiva'; ?>
                    </span>
                </div>

                <?php if ($category['description']): ?>
                <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>

                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="font-semibold"><?php echo $plan_count; ?></span>
                        <span class="ml-1"><?php echo $plan_count === 1 ? 'plan' : 'planes'; ?></span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="categoria_editar.php?id=<?php echo $category['id']; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <?php if ($plan_count === 0): ?>
                        <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        <?php else: ?>
                        <button disabled class="p-2 text-gray-300 cursor-not-allowed rounded-lg" title="No se puede eliminar (tiene planes asociados)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($categories)): ?>
    <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">No hay categorías</h3>
        <p class="text-gray-600 mb-6">Crea tu primera categoría para organizar los planes</p>
        <a href="categoria_crear.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Crear Primera Categoría
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteCategory(id, name) {
    if (confirm(`¿Estás seguro de eliminar la categoría "${name}"?`)) {
        window.location.href = `categoria_eliminar.php?id=${id}`;
    }
}
</script>

<?php include 'footer.php'; ?>
