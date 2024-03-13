<?php
header('Content-Type: application/json');

$period = $_GET['period'] ?? 'daily';

include 'db.php';

if ($conn->connect_error) {
  echo json_encode(['error' => "Connect Error: " . $conn->connect_error]);
  exit;
}

if ($period === 'daily') {
  $query = "SELECT DATE(accessed_at) AS date, COUNT(*) AS count FROM url_accesses GROUP BY DATE(accessed_at) ORDER BY DATE(accessed_at)";
} else if ($period === 'monthly') {
  $query = "SELECT DATE_FORMAT(accessed_at, '%Y-%m') AS month, COUNT(*) AS count FROM url_accesses GROUP BY DATE_FORMAT(accessed_at, '%Y-%m') ORDER BY DATE_FORMAT(accessed_at, '%Y-%m')";
} else {
  echo json_encode(['error' => "Invalid period: $period"]);
  exit;
}

if ($result = $conn->query($query)) {
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
  echo json_encode(['error' => "Query Error: " . $conn->error]);
}

$conn->close();
?>