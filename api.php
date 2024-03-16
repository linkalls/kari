<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $originalUrl = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
  $customPath = filter_input(INPUT_POST, 'customPath', FILTER_SANITIZE_STRING);

  if (!$originalUrl) {
    echo json_encode(["error" => "URLが必要です。"]);
    exit;
  }

  if (!preg_match("~^(?:f|ht)tps?://~i", $originalUrl)) {
    $originalUrl = "http://" . $originalUrl;
  }

  $uniqueValue = microtime();
  $shortUrl = substr(hash('sha256', $originalUrl . $uniqueValue), 0, 8);

  $checkSql = "SELECT * FROM short_urls WHERE short_url = ?";
  $checkStmt = $conn->prepare($checkSql);
  $checkStmt->bind_param("s", $shortUrl);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows > 0) {
    echo json_encode(["error" => "その短縮URLは既に存在します。"]);
    exit;
  } else {
    $sql = "INSERT INTO short_urls (short_url, original_url) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $shortUrl, $originalUrl);

    if ($stmt->execute()) {
      $shortUrlMessage = $_SERVER['HTTP_HOST'] . "/" . $shortUrl;
      echo json_encode(["message" => "短縮URLの生成が完了しました。", "shortUrl" => $shortUrlMessage]);
      exit;
    } else {
      echo json_encode(["error" => "データベースエラー: " . $conn->error]);
      exit;
    }
  }
} else {
  echo json_encode(["error" => "POSTリクエストが必要です。"]);
  exit;
}
?>