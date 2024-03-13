<?php
session_start();
header('Content-Type: application/json');

$period = $_GET['period'] ?? 'daily';

include 'db.php';

if ($conn->connect_error) {
 echo json_encode(['error' => "接続エラー: " . $conn->connect_error]);
 exit;
}

$userId = $_SESSION["user_id"];

if ($period === 'daily') {
 $query = "SELECT DATE(a.accessed_at) AS date, COUNT(*) AS count 
            FROM url_accesses a 
            JOIN short_urls u ON a.short_url_id = u.id
            WHERE u.user_id = ?
            GROUP BY DATE(a.accessed_at) 
            ORDER BY DATE(a.accessed_at)";
} else if ($period === 'monthly') {
 $query = "SELECT DATE_FORMAT(a.accessed_at, '%Y-%m') AS month, COUNT(*) AS count 
            FROM url_accesses a 
            JOIN short_urls u ON a.short_url_id = u.id
            WHERE u.user_id = ?
            GROUP BY DATE_FORMAT(a.accessed_at, '%Y-%m') 
            ORDER BY DATE_FORMAT(a.accessed_at, '%Y-%m')";
} else {
 echo json_encode(['error' => "無効な期間: $period"]);
 exit;
}

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result) {
 $labels = [];
 $accessCounts = [];
 while ($row = $result->fetch_assoc()) {
    $labels[] = $row[$period === 'daily' ? 'date' : 'month'];
    $accessCounts[] = $row['count'];
 }
 $result->free();

 echo json_encode([
    'labels' => $labels,
    'accessCounts' => $accessCounts,
 ]);
} else {
 echo json_encode(['error' => "クエリエラー: " . $conn->error]);
}

$conn->close();
?>