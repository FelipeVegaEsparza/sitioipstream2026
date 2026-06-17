<?php
require_once 'auth.php';

$pdo = getDatabase();

if (!isset($_GET['id'])) {
    header('Location: tutoriales.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, c.color as category_color
    FROM tutorials t
    LEFT JOIN tutorial_categories c ON t.category_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$_GET['id']]);
$tutorial = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutorial) {
    header('Location: tutoriales.php');
    exit;
}

include 'header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <a href="tutoriales.php" class="text-indigo-600 hover:text-indigo-900">
            ← Volver a Tutoriales
        </a>
    </div>

    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($tutorial['title']); ?>
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?php echo htmlspecialchars($tutorial['category_color']); ?>-100 text-<?php echo htmlspecialchars($tutorial['category_color']); ?>-800">
                            <?php echo htmlspecialchars($tutorial['category_name']); ?>
                        </span>
                        <?php
                        $difficultyColors = [
                            'Básico' => 'green',
                            'Intermedio' => 'yellow',
                            'Avanzado' => 'red'
                        ];
                        $color = $difficultyColors[$tutorial['difficulty']] ?? 'gray';
                        ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                            <?php echo htmlspecialchars($tutorial['difficulty']); ?>
                        </span>
                        <?php if ($tutorial['duration']): ?>
                            <span class="text-sm text-gray-500">
                                ⏱️ <?php echo htmlspecialchars($tutorial['duration']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="text-sm text-gray-500">
                            👁️ <?php echo number_format($tutorial['views']); ?> vistas
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="tutorial_editar.php?id=<?php echo $tutorial['id']; ?>" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Editar
                    </a>
                    <?php if ($tutorial['is_active']): ?>
                        <span class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium bg-green-100 text-green-800">
                            ✓ Activo
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-800">
                            Inactivo
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Video Player -->
        <div class="aspect-video bg-black">
            <iframe
                width="100%"
                height="100%"
                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($tutorial['youtube_id']); ?>"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                class="w-full h-full"
            ></iframe>
        </div>

        <!-- Description -->
        <div class="px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Descripción</h2>
            <p class="text-gray-700 whitespace-pre-line">
                <?php echo htmlspecialchars($tutorial['description']); ?>
            </p>
        </div>

        <!-- Details -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Detalles</h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">URL de YouTube</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="<?php echo htmlspecialchars($tutorial['video_url']); ?>" 
                           target="_blank" 
                           class="text-indigo-600 hover:text-indigo-900">
                            <?php echo htmlspecialchars($tutorial['video_url']); ?>
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">ID de YouTube</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($tutorial['youtube_id']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Orden de visualización</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $tutorial['display_order']; ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Fecha de creación</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php echo date('d/m/Y H:i', strtotime($tutorial['created_at'])); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Última actualización</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php echo date('d/m/Y H:i', strtotime($tutorial['updated_at'])); ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
