<?php
// データベース接続スクリプトを読み込む
require 'db.php';

// リクエストURIから短縮URLを取得する
$path = $_SERVER['REQUEST_URI'];
$short_url = ltrim($path, '/');  // 先頭の'/'を削除

// 短縮URLが設定されていない場合はエラーメッセージを出力して終了
if (empty($short_url)) {
  echo "URLパラメータがありません。";
  exit;
}

// 短縮URLに対応するレコードをshort_urlsテーブルから検索する
$sql = "SELECT id, original_url FROM short_urls WHERE short_url = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $short_url);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// レコードが見つかった場合
if ($row) {
  $original_url = $row['original_url'];
  $short_url_id = $row['id'];

  // 元のURLにリダイレクトする
  header("Location: $original_url");

  // アクセスログを保存する
  $referrer = $_SERVER['HTTP_REFERER'] ?? '';
  $client_ip = $_SERVER['REMOTE_ADDR'];
  $stmt = $conn->prepare("INSERT INTO url_accesses (short_url_id, accessed_at, referrer, client_ip) VALUES (?, NOW(), ?, ?)");
  $stmt->bind_param('iss', $short_url_id, $referrer, $client_ip);
  $stmt->execute();

  // 新しく挿入されたレコードのIDを取得する
  $url_access_id = $conn->insert_id;

  // url_accessesテーブルのshort_url_idを更新する
  $stmt = $conn->prepare("UPDATE url_accesses SET short_url_id = ? WHERE id = ?");
  $stmt->bind_param('ii', $short_url_id, $url_access_id);
  $stmt->execute();
} else {
  // レコードが見つからなかった場合はエラーメッセージを出力
  echo "URLが見つかりません。";
}