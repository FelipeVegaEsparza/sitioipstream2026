<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'auth.php';
include 'header.php';

$category_id = $_GET['id'] ?? null;
$error = null;
$success = null;

if (!$category_id) {
    header('Location: categorias.php');
    exit;
}

$pdo = getDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = "El nombre es obligatorio";
    } elseif (empty($slug)) {
        $error = "El slug es obligatorio";
    } else {
        try {
            // Verificar si el slug ya existe en otra categoría
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM plan_categories WHERE slug = ? AND id != ?");
            $stmt_check->execute([$slug, $category_id]);
            
            if ($stmt_check->fetchColumn() > 0) {
                $error = "Ya existe otra categoría con ese slug";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE plan_categories 
                    SET name = ?, slug = ?, description = ?, icon = ?, display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $description, $icon, $display_order, $is_active, $category_id]);
                
                $success = "Categoría actualizada exitosamente";
            }
        } catch (Exception $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Cargar datos de la categoría
$stmt = $pdo->prepare("SELECT * FROM plan_categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: categorias.php');
    exit;
}

// Contar planes asociados
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE category_id = ?");
$stmt_count->execute([$category_id]);
$plan_count = $stmt_count->fetchColumn();
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="categorias.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center mb-4">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Categorías
        </a>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Editar Categoría</h2>
        <p class="text-gray-600">Modifica los detalles de la categoría</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
            <h3 class="text-xl font-bold text-gray-900">Información de la Categoría</h3>
        </div>

        <form method="POST" class="p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                    Nombre *
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    required
                    placeholder="Ej: Radio Online"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                    value="<?php echo htmlspecialchars($category['name']); ?>"
                >
            </div>

            <div>
                <label for="slug" class="block text-sm font-semibold text-gray-700 mb-2">
                    Slug *
                </label>
                <input 
                    type="text" 
                    id="slug" 
                    name="slug" 
                    required
                    placeholder="Ej: radio-online"
                    pattern="[a-z0-9-]+"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-mono"
                    value="<?php echo htmlspecialchars($category['slug']); ?>"
                >
                <p class="mt-1 text-xs text-gray-500">Solo letras minúsculas, números y guiones</p>
            </div>

            <div>
                <label for="icon" class="block text-sm font-semibold text-gray-700 mb-2">
                    Icono/Emoji
                </label>
                <input 
                    type="text" 
                    id="icon" 
                    name="icon" 
                    placeholder="Ej: 🎙️, 📺, 📻"
                    maxlength="10"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-2xl"
                    value="<?php echo htmlspecialchars($category['icon']); ?>"
                >
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                    Descripción
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3"
                    placeholder="Descripción breve de la categoría"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                ><?php echo htmlspecialchars($category['description']); ?></textarea>
            </div>

            <div>
                <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
                    Orden de Visualización
                </label>
                <input 
                    type="number" 
                    id="display_order" 
                    name="display_order" 
                    min="0"
                    placeholder="0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                    value="<?php echo htmlspecialchars($category['display_order']); ?>"
                >
                <p class="mt-1 text-xs text-gray-500">Menor número aparece primero</p>
            </div>

            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="is_active" 
                    name="is_active" 
                    class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                    <?php echo $category['is_active'] ? 'checked' : ''; ?>
                >
                <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700">
                    Categoría activa
                </label>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="categorias.php" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-semibold shadow-lg transform hover:scale-105 transition-all duration-300 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="text-sm font-semibold text-blue-900 mb-1">Información</h4>
                <p class="text-sm text-blue-700">
                    Esta categoría tiene <strong><?php echo $plan_count; ?></strong> <?php echo $plan_count === 1 ? 'plan asociado' : 'planes asociados'; ?>.
                    <?php if ($plan_count > 0): ?>
                        No podrás eliminarla mientras tenga planes asociados.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
