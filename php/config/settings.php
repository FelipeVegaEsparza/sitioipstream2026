<?php
function getSettings(): array {
    static $settings = null;
    if ($settings !== null) return $settings;

    try {
        $pdo = getDatabase();
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = $rows ?: [];
    } catch (Exception $e) {
        $settings = [];
    }

    return $settings;
}

function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}
