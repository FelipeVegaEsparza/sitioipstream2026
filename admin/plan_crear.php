<?php
require_once 'auth.php';

$pdo = getDatabase();
$error = null;

// Obtener categorías para el select
$stmt_categories = $pdo->query("SELECT * FROM plan_categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $plan_name = trim($_POST['plan_name'] ?? '');
    $plan_key = trim($_POST['plan_key'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $demo_url = trim($_POST['demo_url'] ?? '');
    
    // Procesar imagen subida
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Guardar en public_html/uploads/plans/ (la raíz web)
        $upload_dir = __DIR__ . '/../uploads/plans/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid('plan_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image_url = '/uploads/plans/' . $file_name;
            }
        }
    }
    $monthly_price = !empty($_POST['monthly_price']) ? intval($_POST['monthly_price']) : null;
    $annual_price = !empty($_POST['annual_price']) ? intval($_POST['annual_price']) : null;
    $price = $monthly_price ?? $annual_price ?? 0; // Para compatibilidad
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
    
    // Validaciones
    if (empty($plan_name)) {
        $error = "El nombre del plan es obligatorio";
    } elseif (empty($plan_key)) {
        $error = "La clave del plan es obligatoria";
    } elseif (empty($title)) {
        $error = "El título del plan es obligatorio";
    } elseif (empty($monthly_price) && empty($annual_price)) {
        $error = "Debes especificar al menos un precio (mensual o anual)";
    } else {
        try {
            // Verificar si la clave ya existe
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE plan_key = ?");
            $stmt_check->execute([$plan_key]);
            
            if ($stmt_check->fetchColumn() > 0) {
                $error = "Ya existe un plan con esa clave";
            } else {
                // Insertar nuevo plan
                $stmt = $pdo->prepare("
                    INSERT INTO plans (
                        category_id, plan_key, plan_name, title, icon, image_url, description, 
                        features, monthly_price, annual_price, price, 
                        billing_note, demo_url, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $category_id, $plan_key, $plan_name, $title, $icon, $image_url, $description,
                    $features_json, $monthly_price, $annual_price, $price,
                    $billing_note, $demo_url, $is_active
                ]);
                
                $_SESSION['success'] = "Plan creado exitosamente";
                
                // Redirigir inmediatamente
                header("Location: planes.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Error al crear el plan: " . $e->getMessage();
        }
    }
}

include 'header.php';
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
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Crear Nuevo Plan</h2>
        <p class="text-gray-600">Agrega un nuevo plan de suscripción a tu plataforma</p>
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

    <!-- Form -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Información del Plan
            </h3>
        </div>

        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
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
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
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
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
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
                    value="<?php echo htmlspecialchars($_POST['icon'] ?? ''); ?>"
                >
                <p class="mt-1 text-xs text-gray-500">Emoji o icono que representa el plan</p>
            </div>

            <!-- Image Upload -->
            <div>
                <label for="image" class="block text-sm font-semibold text-gray-700 mb-2">
                    Imagen del Plan
                </label>
                <input 
                    type="file" 
                    id="image" 
                    name="image" 
                    accept="image/*"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                >
                <p class="mt-1 text-xs text-gray-500">Sube una imagen (JPG, PNG, WebP). Máximo 2MB</p>
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
                    value="<?php echo htmlspecialchars($_POST['demo_url'] ?? ''); ?>"
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
                    placeholder="Ej: Radio Online - Mensual"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                    value="<?php echo htmlspecialchars($_POST['plan_name'] ?? ''); ?>"
                >
                <p class="mt-1 text-xs text-gray-500">Nombre completo para uso interno</p>
            </div>

            <!-- Plan Key -->
            <div>
                <label for="plan_key" class="block text-sm font-semibold text-gray-700 mb-2">
                    Clave del Plan (plan_key) *
                </label>
                <input 
                    type="text" 
                    id="plan_key" 
                    name="plan_key" 
                    required
                    placeholder="Ej: radio_monthly, tv_annual"
                    pattern="[a-z_]+"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-mono"
                    value="<?php echo htmlspecialchars($_POST['plan_key'] ?? ''); ?>"
                >
                <p class="mt-1 text-xs text-gray-500">Solo letras minúsculas y guiones bajos. Ej: radio_monthly, tv_annual</p>
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
                    placeholder="Transmite solo en audio con todo lo que necesitas: sitio web, app PWA, panel de administración y reproductor de radio profesional."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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
                            value="<?php echo htmlspecialchars($_POST['monthly_price'] ?? ''); ?>"
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
                            value="<?php echo htmlspecialchars($_POST['annual_price'] ?? ''); ?>"
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
                    value="<?php echo htmlspecialchars($_POST['billing_note'] ?? ''); ?>"
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
                    placeholder="Sitio Web Profesional&#10;Aplicación PWA Multiplataforma&#10;Panel de Administración Intuitivo&#10;Dominio .cl o .com GRATIS&#10;50 GB Autodj y Contenido"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-mono text-sm"
                ><?php echo htmlspecialchars($_POST['features'] ?? ''); ?></textarea>
                <p class="mt-1 text-xs text-gray-500">Una característica por línea. Se mostrarán con checkmarks (✓)</p>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="is_active" 
                    name="is_active" 
                    class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                    <?php echo (isset($_POST['is_active']) || !isset($_POST['plan_name'])) ? 'checked' : ''; ?>
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
                    Crear Plan
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
