<?php
require_once __DIR__ . '/../php/config/config.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$slug = $_GET['slug'] ?? '';

if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
    if (!$article) {
        http_response_code(404);
        $page_title = 'Noticia no encontrada';
        $page_content = '<div class="text-center py-20"><h1 class="text-4xl font-bold mb-4">Noticia no encontrada</h1><p class="text-gray-600 mb-8">La noticia que buscas no existe o ha sido eliminada.</p><a href="/noticias" class="text-blue-600 hover:text-blue-800 font-semibold">← Volver a noticias</a></div>';
    } else {
        $page_title = htmlspecialchars($article['title']) . ' | IPStream';
        $page_content = render_article($article);
    }
} else {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM news WHERE is_active = 1");
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = max(1, ceil($total / $per_page));

    $stmt = $pdo->prepare("SELECT * FROM news WHERE is_active = 1 ORDER BY published_at DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute();
    $news = $stmt->fetchAll();

    $page_title = 'Noticias | IPStream - Tu Radio Online';
    $page_content = render_list($news, $page, $total_pages);
}

function render_article($a) {
    $date = date('d/m/Y H:i', strtotime($a['published_at']));
    $img = $a['image'] ? '<img src="' . htmlspecialchars($a['image']) . '" alt="' . htmlspecialchars($a['title']) . '" class="w-full rounded-2xl shadow-lg mb-8 max-h-96 object-cover">' : '';
    return '
    <article class="max-w-4xl mx-auto">
        <a href="/noticias" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium mb-8 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver a noticias
        </a>
        <div class="text-sm text-blue-600 font-medium mb-4">' . $date . ' · Por ' . htmlspecialchars($a['author']) . '</div>
        <h1 class="text-4xl md:text-5xl font-bold mb-6">' . htmlspecialchars($a['title']) . '</h1>
        ' . ($a['excerpt'] ? '<p class="text-xl text-gray-600 mb-8">' . htmlspecialchars($a['excerpt']) . '</p>' : '') . '
        ' . $img . '
        <div class="prose prose-lg max-w-none">' . $a['content'] . '</div>
    </article>';
}

function render_list($news, $page, $total_pages) {
    if (empty($news)) {
        return '<div class="text-center py-20"><h2 class="text-2xl font-bold mb-4">No hay noticias aún</h2><p class="text-gray-600">Pronto publicaremos nuestras primeras noticias. ¡Vuelve pronto!</p></div>';
    }
    $cards = '';
    foreach ($news as $item) {
        $date = date('d/m/Y', strtotime($item['published_at']));
        $img = $item['image']
            ? '<img src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['title']) . '" class="w-full h-52 object-cover">'
            : '<div class="w-full h-52 bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg></div>';
        $excerpt = $item['excerpt'] ? htmlspecialchars($item['excerpt']) : '';
        $cards .= '
        <a href="/noticias/?slug=' . urlencode($item['slug']) . '" class="group bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-2">
            ' . $img . '
            <div class="p-6">
                <p class="text-xs text-blue-600 font-medium mb-2">' . $date . '</p>
                <h3 class="font-bold text-xl mb-2 group-hover:text-blue-600 transition-colors">' . htmlspecialchars($item['title']) . '</h3>
                <p class="text-gray-600">' . $excerpt . '</p>
            </div>
        </a>';
    }

    $pagination = '';
    if ($total_pages > 1) {
        $pagination = '<div class="flex justify-center mt-12 space-x-2">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
            $pagination .= '<a href="/noticias/?page=' . $i . '" class="px-4 py-2 rounded-lg font-medium transition-colors ' . $active . '">' . $i . '</a>';
        }
        $pagination .= '</div>';
    }

    return '
    <div class="text-center mb-16 animate-fade-in">
        <span class="inline-block py-1 px-3 rounded-full bg-blue-100 text-blue-600 text-sm font-medium mb-4">NOTICIAS</span>
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Noticias y <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">actualizaciones</span></h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">Entérate de las últimas novedades, actualizaciones y consejos para tu radio online</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        ' . $cards . '
    </div>
    ' . $pagination;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <meta name="description" content="Noticias, actualizaciones y consejos sobre IPStream - Tu plataforma de radio online.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/_astro/index.ByjUOm8d.css">
    <style>
        .prose h2 { font-size: 1.75rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; }
        .prose h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .prose p { margin-bottom: 1rem; line-height: 1.75; }
        .prose ul, .prose ol { margin-bottom: 1rem; padding-left: 1.5rem; }
        .prose li { margin-bottom: 0.25rem; }
        .prose a { color: #2563eb; text-decoration: underline; }
        .prose img { border-radius: 0.75rem; margin: 1.5rem 0; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2 px-4 text-center text-sm">
        <div class="container mx-auto flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <span>🎉 <strong>7 DÍAS GRATIS</strong> — Prueba IPStream sin compromiso. Radio Online desde <strong>$19.990/mes</strong></span>
            <a href="/landing" class="ml-3 bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-1 rounded-full text-xs font-medium transition-all duration-300">Reclamar</a>
        </div>
    </div>
    <header class="sticky top-0 z-50 bg-white shadow-lg">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="/" class="flex items-center"><img src="/images/logos/logo.png" alt="IPStream Logo" class="h-12 w-auto"></a>
            <nav class="hidden lg:flex space-x-8">
                <a href="/" class="nav-link py-2 px-3 rounded-lg hover:bg-blue-50 transition-all">Inicio</a>
                <a href="/planes" class="nav-link py-2 px-3 rounded-lg hover:bg-blue-50 transition-all">Planes</a>
                <a href="/caracteristicas" class="nav-link py-2 px-3 rounded-lg hover:bg-blue-50 transition-all">Características</a>
                <a href="/tutoriales" class="nav-link py-2 px-3 rounded-lg hover:bg-blue-50 transition-all">Tutoriales</a>
                <a href="/noticias" class="nav-link py-2 px-3 rounded-lg hover:bg-blue-50 transition-all font-semibold text-blue-600">Noticias</a>
                <a href="/soporte" class="nav-link py-2 px-3 rounded-lg hover:bg-blue-50 transition-all">Soporte</a>
            </nav>
            <a href="/landing" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2 rounded-xl font-medium hover:shadow-lg transition-all">Quiero Contratar</a>
        </div>
    </header>
    <main class="flex-grow">
        <section class="py-24 bg-white">
            <div class="container mx-auto px-6">
                <?= $page_content ?>
            </div>
        </section>
    </main>
    <footer class="bg-gradient-to-br from-gray-900 to-blue-900 text-white py-16">
        <div class="container mx-auto px-6 text-center">
            <p class="text-blue-200">&copy; 2025 IPStream. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
