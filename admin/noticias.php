<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$countStmt = $pdo->query("SELECT COUNT(*) as total FROM news");
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, ceil($total / $per_page));

$stmt = $pdo->prepare("SELECT * FROM news ORDER BY published_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Noticias</h1>
        <a href="noticia_editar.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">+ Nueva Noticia</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Autor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Publicado</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Activo</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($news as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 max-w-xs truncate"><?= htmlspecialchars($item['title']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($item['author']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($item['published_at'])) ?></td>
                    <td class="px-6 py-4 text-center">
                        <span class="<?= $item['is_active'] ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100' ?> px-2 py-1 rounded-full text-xs font-medium">
                            <?= $item['is_active'] ? 'Sí' : 'No' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        <a href="noticia_editar.php?id=<?= $item['id'] ?>" class="text-blue-600 hover:text-blue-800">Editar</a>
                        <form method="POST" action="noticia_eliminar.php" class="inline" onsubmit="return confirm('¿Eliminar esta noticia?')"><?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($news)): ?>
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No hay noticias aún.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center mt-6 space-x-2">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
