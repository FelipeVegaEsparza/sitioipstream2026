<?php
function logPaymentEvent($pdo, string $commerceOrder, string $eventType, array $eventData): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_logs (commerce_order, event_type, event_data, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$commerceOrder, $eventType, json_encode($eventData)]);
    } catch (Exception $e) {
        error_log('Error logging event: ' . $e->getMessage());
    }
}
