<?php
require_once 'auth.php';
include 'header.php';

$pdo = getDatabase();

$stmt = $pdo->query("SELECT * FROM client_portfolio ORDER BY display_order ASC, created_at DESC");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Clientes Portafolio</h1>
            <p class="text-gray-600 mt-1">Gestiona los clientes que se muestran en la página principal.</p>
        </div>
        <a href="clientes-portafolio-crear.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Añadir Cliente
        </a>
    </div>

    <?php if (empty($clients)): ?>
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">No hay clientes en el portafolio</h3>
            <p class="mt-2 text-gray-500">Agrega tu primer cliente para mostrarlo en la página principal.</p>
            <a href="clientes-portafolio-crear.php" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">Añadir Cliente</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($clients as $client): ?>
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300">
                    <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                        <?php if ($client['image_url']): ?>
                            <img src="<?= h($client['image_url']) ?>" alt="<?= h($client['title']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-bold text-gray-900"><?= h($client['title']) ?></h3>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $client['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $client['is_active'] ? 'Activo' : 'Inactivo' ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4 line-clamp-3"><?= h($client['description']) ?></p>
                        <?php if ($client['project_url']): ?>
                            <a href="<?= h($client['project_url']) ?>" target="_blank" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 mb-4">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Visitar proyecto
                            </a>
                        <?php endif; ?>
                        <div class="flex space-x-2 pt-3 border-t border-gray-100">
                            <a href="clientes-portafolio-editar.php?id=<?= $client['id'] ?>" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Editar</a>
                            <form method="POST" action="eliminar.php" class="inline" onsubmit="return confirm('¿Eliminar este cliente del portafolio?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="type" value="client_portfolio">
                                <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                <input type="hidden" name="redirect" value="clientes-portafolio.php">
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
