<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function ensureSettingsTable(): void {
    try {
        $pdo = getDatabase();
        $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
            `key` varchar(100) NOT NULL,
            `value` text DEFAULT NULL,
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $pdo->exec("INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
            ('social_facebook', '#'),
            ('social_twitter', '#'),
            ('social_instagram', '#'),
            ('social_youtube', '#'),
            ('social_tiktok', '#')");
    } catch (Exception $e) {
    }
}

function getSettings(): array {
    static $settings = null;
    if ($settings !== null) return $settings;

    try {
        $pdo = getDatabase();
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = $rows ?: [];
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), '42S02')) {
            ensureSettingsTable();
            return getSettings();
        }
        $settings = [];
    }

    return $settings;
}

function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}
