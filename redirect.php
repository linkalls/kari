<?php
require 'db.php';

# ini_set('display_errors', 1);
# error_reporting(E_ALL);

if (!isset($_GET['url'])) {
  echo "URL parameter is missing.";
  exit;
}

$short_url = $_GET['url'];

$sql = "SELECT id, original_url FROM short_urls WHERE short_url = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $short_url);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
  $original_url = $row['original_url'];
  $short_url_id = $row['id'];
  header("Location: $original_url");

  // Save access log
  $referrer = $short_url;
  $client_ip = $_SERVER['REMOTE_ADDR'];
  $stmt = $conn->prepare("INSERT INTO url_accesses (short_url_id, accessed_at, referrer, client_ip) VALUES (?, NOW(), ?, ?)");
  $stmt->bind_param('iss', $short_url_id, $referrer, $client_ip);
  $stmt->execute();
} else {
  echo "URL not found.";
}

