<?php
require_once __DIR__ . '/../php/config/config.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$countStmt = $pdo->query("SELECT COUNT(*) as total FROM client_portfolio WHERE is_active = 1");
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, ceil($total / $per_page));

$stmt = $pdo->prepare("SELECT title, description, image_url, project_url FROM client_portfolio WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$clients = $stmt->fetchAll();

$page_title = 'Nuestros Clientes | IPStream';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="Conoce los proyectos y clientes que confían en IPStream para su radio online." />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .animate-fade-in { opacity: 0; animation: fadeIn 0.8s ease-out forwards; }
        @keyframes fadeIn { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <header class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-100">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="/" class="flex items-center">
                <img src="/images/logos/logo.png" alt="IPStream" class="h-10 w-auto">
            </a>
            <nav class="hidden lg:flex items-center space-x-1">
                <a href="/" class="px-3 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-300 font-medium">Inicio</a>
                <a href="/planes" class="px-3 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-300 font-medium">Planes</a>
                <a href="/caracteristicas" class="px-3 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-300 font-medium">Características</a>
                <div class="relative group">
                    <button class="px-3 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-300 font-medium flex items-center">
                        Recursos
                        <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all duration-200 transform -translate-y-1 group-hover:translate-y-0 z-50">
                        <a href="/tutoriales" class="block px-4 py-2.5 text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors font-medium">Tutoriales</a>
                        <a href="/clientes" class="block px-4 py-2.5 text-blue-600 hover:bg-blue-50 transition-colors font-medium">Clientes</a>
                        <a href="/noticias" class="block px-4 py-2.5 text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors font-medium">Noticias</a>
                        <a href="/soporte" class="block px-4 py-2.5 text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors font-medium">Soporte</a>
                        <hr class="my-1 border-gray-100">
                        <a href="/pago-mensual" class="block px-4 py-2.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors text-sm">Pago Mensual</a>
                    </div>
                </div>
            </nav>
            <a href="/landing" class="hidden md:inline-flex items-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2 rounded-xl font-medium hover:shadow-lg transition-all">Quiero Contratar</a>
        </div>
    </header>

    <main class="flex-grow">
        <section class="py-20 bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Nuestros Clientes</h1>
                <p class="text-xl text-blue-100 max-w-2xl mx-auto">Proyectos que transmiten con IPStream y confían en nuestra plataforma.</p>
            </div>
        </section>

        <section class="py-16">
            <div class="container mx-auto px-6">
                <?php if (empty($clients)): ?>
                    <div class="text-center py-20">
                        <svg class="mx-auto h-20 w-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <h2 class="mt-6 text-2xl font-bold text-gray-900">Próximamente</h2>
                        <p class="mt-2 text-gray-500">Estaremos mostrando nuestros clientes aquí.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        <?php foreach ($clients as $c): ?>
                            <a href="<?= htmlspecialchars($c['project_url'] ?: '#') ?>" target="_blank" class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border border-gray-100 hover:-translate-y-2">
                                <div class="aspect-[4/3] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                                    <?php if ($c['image_url']): ?>
                                        <img src="<?= htmlspecialchars($c['image_url']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-900 text-sm text-center group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($c['title']) ?></h3>
                                    <?php if ($c['description']): ?>
                                        <p class="text-xs text-gray-500 mt-1 text-center line-clamp-2"><?= htmlspecialchars($c['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center items-center space-x-4 mt-12">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">&larr; Anterior</a>
                            <?php endif; ?>
                            <span class="text-sm text-gray-600">Página <?= $page ?> de <?= $total_pages ?></span>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">Siguiente &rarr;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="container mx-auto px-6 text-center">
            <img src="/images/logos/logo.png" alt="IPStream" class="h-10 mx-auto mb-4 opacity-50">
            <p class="text-sm">&copy; <?= date('Y') ?> IPStream. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
