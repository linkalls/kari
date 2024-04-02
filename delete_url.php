<?php


session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] == 0) {
  header("Location: login.php");
  exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $shortUrl = $_POST['shortUrl'];
  
  // url_accessesテーブルから該当のURLに関連するレコードを削除
  $sql = "DELETE a FROM url_accesses a INNER JOIN short_urls u ON a.short_url_id = u.id WHERE u.short_url = ? AND u.user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('si', $shortUrl, $_SESSION['user_id']);
  $stmt->execute();
  $stmt->close();

  // short_urlsテーブルから該当のURLを削除
  $sql = "DELETE FROM short_urls WHERE short_url = ? AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('si', $shortUrl, $_SESSION['user_id']);
  $result = $stmt->execute();
  $stmt->close();

  // 削除が成功したらセッションにメッセージを保存
  if ($result) {
    $_SESSION['flash_message'] = '短縮URLを削除しました。';
  }

  // JSON形式で結果を返す
  header('Content-Type: application/json');
  echo json_encode(['success' => $result]);
}
?>