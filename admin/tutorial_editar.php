<?php
require_once 'auth.php';

$pdo = getDatabase();
$tutorial = null;
$isEdit = false;

// Si hay ID, es edición
if (isset($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare("SELECT * FROM tutorials WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $tutorial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tutorial) {
        header('Location: tutoriales.php');
        exit;
    }
}

// Obtener categorías
$stmt = $pdo->query("SELECT * FROM tutorial_categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $video_url = $_POST['video_url'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $difficulty = $_POST['difficulty'] ?? 'beginner';
    $category_id = $_POST['category_id'] ?? '';
    $display_order = $_POST['display_order'] ?? 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title) || empty($description) || empty($video_url) || empty($category_id)) {
        $error = 'Por favor completa todos los campos requeridos';
    } else {
        try {
            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE tutorials 
                    SET title = ?, description = ?, video_url = ?, 
                        duration = ?, difficulty = ?, category_id = ?, display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $video_url, $duration, $difficulty, $category_id, $display_order, $is_active, $_GET['id']]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO tutorials (title, description, video_url, duration, difficulty, category_id, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $video_url, $duration, $difficulty, $category_id, $display_order, $is_active]);
            }
            
            header('Location: tutoriales.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="tutoriales.php" class="text-indigo-600 hover:text-indigo-900">
            ← Volver a Tutoriales
        </a>
    </div>

    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                <?php echo $isEdit ? 'Editar Tutorial' : 'Nuevo Tutorial'; ?>
            </h3>

            <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?= csrfField() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Título *</label>
                    <input type="text" name="title" required
                           value="<?php echo htmlspecialchars($tutorial['title'] ?? ''); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción *</label>
                    <textarea name="description" rows="4" required
                              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($tutorial['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">URL de YouTube *</label>
                    <input type="url" name="video_url" required
                           value="<?php echo htmlspecialchars($tutorial['video_url'] ?? ''); ?>"
                           placeholder="https://www.youtube.com/watch?v=..."
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Ejemplo: https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Categoría *</label>
                        <select name="category_id" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($tutorial['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duración</label>
                        <input type="text" name="duration"
                               value="<?php echo htmlspecialchars($tutorial['duration'] ?? ''); ?>"
                               placeholder="10:30"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-sm text-gray-500">Formato: MM:SS</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dificultad</label>
                        <select name="difficulty"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Básico" <?php echo ($tutorial['difficulty'] ?? 'Básico') == 'Básico' ? 'selected' : ''; ?>>Básico</option>
                            <option value="Intermedio" <?php echo ($tutorial['difficulty'] ?? '') == 'Intermedio' ? 'selected' : ''; ?>>Intermedio</option>
                            <option value="Avanzado" <?php echo ($tutorial['difficulty'] ?? '') == 'Avanzado' ? 'selected' : ''; ?>>Avanzado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Orden de visualización</label>
                        <input type="number" name="display_order" min="0"
                               value="<?php echo htmlspecialchars($tutorial['display_order'] ?? 0); ?>"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           <?php echo ($tutorial['is_active'] ?? 1) ? 'checked' : ''; ?>
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Tutorial activo (visible en el sitio)
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="tutoriales.php" 
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <?php echo $isEdit ? 'Actualizar' : 'Crear'; ?> Tutorial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
