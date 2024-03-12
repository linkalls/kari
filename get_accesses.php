<?php
try {
    $stmt = $pdo->prepare("INSERT INTO url_accesses (short_url_id, accessed_at, referrer) VALUES (?, NOW(), ?)");
    $stmt->execute([$row['id'], $_SERVER['HTTP_REFERER'] ?? '']);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>