<?php
require_once 'auth.php';

echo "<h1>Test de subida de archivos</h1>";
echo "<p><strong>Directorio actual:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Directorio dist:</strong> " . realpath(__DIR__ . '/../dist/') . "</p>";
echo "<p><strong>Directorio uploads:</strong> " . __DIR__ . '/../dist/uploads/plans/' . "</p>";

$upload_dir = __DIR__ . '/../dist/uploads/plans/';
echo "<p><strong>¿Existe el directorio?</strong> " . (file_exists($upload_dir) ? 'Sí' : 'No') . "</p>";
echo "<p><strong>¿Es escribible?</strong> " . (is_writable(dirname($upload_dir)) ? 'Sí' : 'No') . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h2>Resultado de la subida:</h2>";
    echo "<pre>";
    print_r($_FILES['test_file']);
    echo "</pre>";
    
    if (!file_exists($upload_dir)) {
        $created = mkdir($upload_dir, 0755, true);
        echo "<p>Directorio creado: " . ($created ? 'Sí' : 'No') . "</p>";
    }
    
    $file_name = 'test_' . time() . '.png';
    $file_path = $upload_dir . $file_name;
    
    echo "<p><strong>Intentando guardar en:</strong> $file_path</p>";
    
    if (move_uploaded_file($_FILES['test_file']['tmp_name'], $file_path)) {
        echo "<p style='color: green;'><strong>✓ Archivo guardado exitosamente!</strong></p>";
        echo "<p>URL: /uploads/plans/$file_name</p>";
        echo "<img src='/uploads/plans/$file_name' style='max-width: 300px;'>";
    } else {
        echo "<p style='color: red;'><strong>✗ Error al guardar el archivo</strong></p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" accept="image/*" required>
    <button type="submit">Subir archivo de prueba</button>
</form>
