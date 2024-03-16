<?php
// データベース接続スクリプトを読み込む
require 'db.php';

// リクエストURIから短縮URLを取得する
$path = $_SERVER['REQUEST_URI'];
$short_url = ltrim(urldecode($path), '/');  // 先頭の'/'を削除

// 短縮URLが設定されていない場合はエラーメッセージを出力して終了
if (empty($short_url)) {
  echo "URLパラメータがありません。";
  exit;
}

// 短縮URLに対応するレコードをshort_urlsテーブルから検索する
$sql = "SELECT id, original_url, user_id FROM short_urls WHERE short_url = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $short_url);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// レコードが見つかった場合
if ($row) {
  $original_url = $row['original_url'];
  $short_url_id = $row['id'];
  $user_id = $row['user_id'];

  // 元のURLにリダイレクトする
  header("Location: $original_url");

  // ユーザーIDが0でない場合のみアクセスログを保存する
  if ($user_id != 0) {
    // アクセスログを保存する
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $client_ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // 同じIPからの最後のアクセスを検索
    $sql = "SELECT accessed_at FROM url_accesses WHERE client_ip = ? ORDER BY accessed_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $client_ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_access = $result->fetch_assoc();

    // 最後のアクセスから1分以上経過している場合のみログを保存
    if (!$last_access || time() - strtotime($last_access['accessed_at']) > 60) {
      $stmt = $conn->prepare("INSERT INTO url_accesses (short_url_id, accessed_at, referrer, client_ip, user_agent) VALUES (?, NOW(), ?, ?, ?)");
      $stmt->bind_param('isss', $short_url_id, $referrer, $client_ip, $user_agent);
      $stmt->execute();
    }
  }
} else {
  // レコードが見つからなかった場合はエラーメッセージを出力
  echo "URLが見つかりません。";
}