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

  // 元のURLが有効なURLであることを確認する
  if (filter_var($original_url, FILTER_VALIDATE_URL)) {
    // 元のURLにリダイレクトする
    header("Location: $original_url");
  } else {
    // 元のURLが無効な場合はエラーメッセージを出力
    echo "無効なURLです。";
    exit;
  }

  // ユーザーIDが0でない場合、または特定のUserAgentでない場合のみアクセスログを保存する
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  $ignored_user_agents = [
    'facebookexternalhit/1.1;line-poker/1.0',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)'
  ];
  if ($user_id != 0 && !in_array($user_agent, $ignored_user_agents)) {
    // アクセスログを保存する
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $client_ip = $_SERVER['REMOTE_ADDR'];

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