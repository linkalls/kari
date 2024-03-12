<?php
try {
    $stmt = $pdo->prepare("INSERT INTO url_accesses (short_url_id, accessed_at, referrer, client_ip) VALUES (?, NOW(), ?, ?)");
    $stmt->execute([$row['id'], $_SERVER['HTTP_REFERER'] ?? '', $_SERVER['REMOTE_ADDR']]);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>