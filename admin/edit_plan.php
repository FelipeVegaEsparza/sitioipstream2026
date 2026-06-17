<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'auth.php';
include 'header.php';

$plan_id = $_GET['id'] ?? null;
$error = null;
$success = null;

if (!$plan_id) {
    echo "<p class='text-red-500'>No se ha especificado un plan.</p>";
    include 'footer.php';
    exit;
}

$pdo = getDatabase();

// Obtener categorías para el select
$stmt_categories = $pdo->query("SELECT * FROM plan_categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $plan_name = trim($_POST['plan_name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $demo_url = trim($_POST['demo_url'] ?? '');
    
    // Obtener imagen actual
    $stmt_img = $pdo->prepare("SELECT image_url FROM plans WHERE id = ?");
    $stmt_img->execute([$plan_id]);
    $current_plan = $stmt_img->fetch(PDO::FETCH_ASSOC);
    $image_url = $current_plan['image_url'] ?? '';
    
    // Procesar nueva imagen si se subió
    $newUrl = uploadImage('image', 'plans', $image_url);
    if ($newUrl) {
        $image_url = $newUrl;
    }
    $monthly_price = !empty($_POST['monthly_price']) ? intval($_POST['monthly_price']) : null;
    $annual_price = !empty($_POST['annual_price']) ? intval($_POST['annual_price']) : null;
    $price = $monthly_price ?? $annual_price ?? 0;
    $billing_note = trim($_POST['billing_note'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Procesar características
    $features = [];
    if (!empty($_POST['features'])) {
        $features_raw = explode("\n", $_POST['features']);
        foreach ($features_raw as $feature) {
            $feature = trim($feature);
            if (!empty($feature)) {
                $features[] = $feature;
            }
        }
    }
    $features_json = json_encode($features, JSON_UNESCAPED_UNICODE);
    
    if (empty($plan_name)) {
        $error = "El nombre del plan es obligatorio";
    } elseif (empty($title)) {
        $error = "El título del plan es obligatorio";
    } elseif (empty($monthly_price) && empty($annual_price)) {
        $error = "Debes especificar al menos un precio (mensual o anual)";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE plans SET 
                    category_id = ?, plan_name = ?, title = ?, icon = ?, image_url = ?, description = ?,
                    features = ?, monthly_price = ?, annual_price = ?, price = ?,
                    billing_note = ?, demo_url = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $category_id, $plan_name, $title, $icon, $image_url, $description,
                $features_json, $monthly_price, $annual_price, $price,
                $billing_note, $demo_url, $is_active, $plan_id
            ]);
            $success = "Plan actualizado exitosamente";
            
            // Recargar datos del plan
            $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
} else {
    // Cargar datos del plan
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$plan) {
    echo "<p class='text-red-500'>Plan no encontrado.</p>";
    include 'footer.php';
    exit;
}

// Obtener estadísticas de uso del plan
$stmt_usage = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE plan_id = ?");
$stmt_usage->execute([$plan_id]);
$usage = $stmt_usage->fetch(PDO::FETCH_ASSOC);
$total_orders = $usage['total'];

$stmt_active = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE plan_id = ? AND status = 'active'");
$stmt_active->execute([$plan_id]);
$active_subs = $stmt_active->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <a href="planes.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center mb-4">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Planes
        </a>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Editar Plan</h2>
        <p class="text-gray-600">Modifica los detalles del plan de suscripción</p>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Column -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Información del Plan
                    </h3>
                </div>

                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6"><?= csrfField() ?>
                    <!-- Plan Key (Read-only) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Clave del Plan
                        </label>
                        <div class="px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg">
                            <code class="text-sm text-gray-700 font-mono"><?php echo htmlspecialchars($plan['plan_key']); ?></code>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">La clave del plan no se puede modificar</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-semibold text-gray-700 mb-2">
                            Categoría *
                        </label>
                        <select 
                            id="category_id" 
                            name="category_id" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                        >
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($plan['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['icon'] . ' ' . $cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Categoría a la que pertenece este plan</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                            Título del Plan *
                        </label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            required
                            placeholder="Ej: Radio Online"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                            value="<?php echo htmlspecialchars($plan['title'] ?? ''); ?>"
                        >
                        <p class="mt-1 text-xs text-gray-500">Título principal que se muestra en la tarjeta del plan</p>
                    </div>

                    <!-- Icon -->
                    <div>
                        <label for="icon" class="block text-sm font-semibold text-gray-700 mb-2">
                            Icono/Emoji
                        </label>
                        <input 
                            type="text" 
                            id="icon" 
                            name="icon" 
                            placeholder="Ej: 🎙️, 📺, 🎵"
                            maxlength="10"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-2xl"
                            value="<?php echo htmlspecialchars($plan['icon'] ?? ''); ?>"
                        >
                        <p class="mt-1 text-xs text-gray-500">Emoji o icono que representa el plan</p>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label for="image" class="block text-sm font-semibold text-gray-700 mb-2">
                            Imagen del Plan
                        </label>
                        <?php if (!empty($plan['image_url'])): ?>
                            <div class="mb-3">
                                <img src="<?php echo htmlspecialchars($plan['image_url']); ?>" alt="Imagen actual" class="max-w-xs rounded-lg border border-gray-300">
                                <p class="text-xs text-gray-500 mt-1">Imagen actual</p>
                            </div>
                        <?php endif; ?>
                        <input 
                            type="file" 
                            id="image" 
                            name="image" 
                            accept="image/*"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                        >
                        <p class="mt-1 text-xs text-gray-500">Sube una nueva imagen para reemplazar la actual (JPG, PNG, WebP). Máximo 2MB</p>
                        <div id="image-preview" class="mt-2 hidden">
                            <img src="" alt="Preview" class="max-w-xs rounded-lg border border-gray-300">
                        </div>
                    </div>

                    <!-- Demo URL -->
                    <div>
                        <label for="demo_url" class="block text-sm font-semibold text-gray-700 mb-2">
                            Link de Ejemplo/Demo
                        </label>
                        <input 
                            type="url" 
                            id="demo_url" 
                            name="demo_url" 
                            placeholder="https://demo.ejemplo.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                            value="<?php echo htmlspecialchars($plan['demo_url'] ?? ''); ?>"
                        >
                        <p class="mt-1 text-xs text-gray-500">URL del demo o ejemplo del plan (se abrirá al hacer clic en "Ver Ejemplo")</p>
                    </div>

                    <!-- Plan Name (Internal) -->
                    <div>
                        <label for="plan_name" class="block text-sm font-semibold text-gray-700 mb-2">
                            Nombre Interno del Plan *
                        </label>
                        <input 
                            type="text" 
                            id="plan_name" 
                            name="plan_name" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                            value="<?php echo htmlspecialchars($plan['plan_name']); ?>"
                        >
                        <p class="mt-1 text-xs text-gray-500">Nombre completo para uso interno</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="3"
                            placeholder="Transmite solo en audio con todo lo que necesitas..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                        ><?php echo htmlspecialchars($plan['description'] ?? ''); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Descripción breve del plan</p>
                    </div>

                    <!-- Prices -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Monthly Price -->
                        <div>
                            <label for="monthly_price" class="block text-sm font-semibold text-gray-700 mb-2">
                                Precio Mensual (CLP)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3 text-gray-500 font-semibold">$</span>
                                <input 
                                    type="number" 
                                    id="monthly_price" 
                                    name="monthly_price" 
                                    min="0"
                                    step="1"
                                    placeholder="29990"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                    value="<?php echo htmlspecialchars($plan['monthly_price'] ?? ''); ?>"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Dejar vacío si no aplica</p>
                        </div>

                        <!-- Annual Price -->
                        <div>
                            <label for="annual_price" class="block text-sm font-semibold text-gray-700 mb-2">
                                Precio Anual (CLP)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-3 text-gray-500 font-semibold">$</span>
                                <input 
                                    type="number" 
                                    id="annual_price" 
                                    name="annual_price" 
                                    min="0"
                                    step="1"
                                    placeholder="269990"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                    value="<?php echo htmlspecialchars($plan['annual_price'] ?? ''); ?>"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Dejar vacío si no aplica</p>
                        </div>
                    </div>

                    <!-- Billing Note -->
                    <div>
                        <label for="billing_note" class="block text-sm font-semibold text-gray-700 mb-2">
                            Nota de Facturación
                        </label>
                        <input 
                            type="text" 
                            id="billing_note" 
                            name="billing_note" 
                            placeholder="IVA Incluido - Facturación mensual, sin contratos de permanencia"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                            value="<?php echo htmlspecialchars($plan['billing_note'] ?? ''); ?>"
                        >
                        <p class="mt-1 text-xs text-gray-500">Información adicional sobre facturación</p>
                    </div>

                    <!-- Features -->
                    <div>
                        <label for="features" class="block text-sm font-semibold text-gray-700 mb-2">
                            Características del Plan
                        </label>
                        <textarea 
                            id="features" 
                            name="features" 
                            rows="6"
                            placeholder="Sitio Web Profesional&#10;Aplicación PWA Multiplataforma&#10;Panel de Administración Intuitivo"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-mono text-sm"
                        ><?php 
                            if (!empty($plan['features'])) {
                                $features = json_decode($plan['features'], true);
                                if (is_array($features)) {
                                    echo htmlspecialchars(implode("\n", $features));
                                }
                            }
                        ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Una característica por línea. Se mostrarán con checkmarks (✓)</p>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="is_active" 
                            name="is_active" 
                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                            <?php echo $plan['is_active'] ? 'checked' : ''; ?>
                        >
                        <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700">
                            Plan activo (visible para los clientes)
                        </label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="planes.php" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
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
        </div>

        <!-- Stats Column -->
        <div class="space-y-6">
            <!-- Usage Stats -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-blue-50 border-b border-gray-200">
                    <h4 class="text-lg font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Estadísticas
                    </h4>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total de Órdenes</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $total_orders; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Suscripciones Activas</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $active_subs; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Ingresos Potenciales</p>
                        <p class="text-2xl font-bold text-purple-600">$<?php echo number_format($plan['price'] * $active_subs, 0, ',', '.'); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Basado en suscripciones activas</p>
                    </div>
                </div>
            </div>

            <!-- Plan Info -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                    <h4 class="text-lg font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Información
                    </h4>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">ID del Plan</p>
                        <p class="font-semibold text-gray-900">#<?php echo $plan['id']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Creado</p>
                        <p class="font-semibold text-gray-900"><?php echo date("d/m/Y H:i", strtotime($plan['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Última Actualización</p>
                        <p class="font-semibold text-gray-900"><?php echo date("d/m/Y H:i", strtotime($plan['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de imagen
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            const img = preview.querySelector('img');
            img.src = e.target.result;
            preview.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'footer.php'; ?>
