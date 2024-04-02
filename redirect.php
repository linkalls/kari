<?php

// データベース接続スクリプトが存在するか確認する
if (!file_exists('db.php')) {
  // データベース接続スクリプトが存在しない場合はsetup-kari.phpにリダイレクトする
  header("Location: setup-kari.php");
  exit;
}
// データベース接続スクリプトを読み込む
require 'db.php';

// リクエストURIから短縮URLを取得する
$path = $_SERVER['REQUEST_URI'];
$short_url = ltrim(urldecode($path), '/');  // 先頭の'/'を削除

// エラーメッセージを格納する変数
$error_message = '';

// 短縮URLが設定されていない場合はエラーメッセージを設定
if (empty($short_url)) {
  $error_message = "この短縮urlは存在しないか削除された可能性があります。";
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
    // 元のURLが無効な場合はエラーメッセージを設定
    $error_message = "無効なURLです。";
  }

  // ユーザーIDが0でない場合、または特定のUserAgentでない場合のみアクセスログを保存する
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  $ignored_user_agents = [
    'facebookexternalhit/1.1;line-poker/1.0',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
    'Twitterbot/1.0',
    'WhatsApp/2.19.221 A',
    'help@dataminr.com',
    'Applebot',
    'trendictionbot0.5.0',
    
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
  // レコードが見つからなかった場合はエラーメッセージを設定
  $error_message = "URLが見つかりません。存在しないか削除された可能性があります。";
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Error</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
  <style>
    #menu {
      display: none;
    }
    #menu:checked ~ .menu {
      display: block;
    }
  </style>
</head>
<body class="bg-gray-200 text-gray-800 antialiased font-sans min-h-screen flex flex-col">
  <nav class="bg-gray-800 text-white px-6 py-4">
    <input type="checkbox" id="menu" class="hidden">
    <label for="menu" class="cursor-pointer md:hidden block">
      <svg class="fill-current text-white" width="20" height="20" viewBox="0 0 20 20">
        <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
      </svg>
    </label>
    <div class="menu hidden md:block">
      <a href="/" class="text-white no-underline hover:text-gray-200 mr-4">Home</a>
      <a href="/about.html" class="text-white no-underline hover:text-gray-200 mr-4">About</a>
      <a href="/contact" class="text-white no-underline hover:text-gray-200">Contact</a>
    </div>
  </nav>

  <main class="mx-4 sm:container sm:mx-auto sm:max-w-xl sm:p-6 flex-grow">
    <h1 class="mt-8 text-3xl font-bold text-red-500 mb-4">エラーが発生しました</h1>
    <p class="text-lg"><?php echo $error_message; ?></p>
  </main>

  <footer class="bg-gray-800 text-white text-center p-4 mt-8">
    <p>Copyright &copy; 2024 kariter</p>
  </footer>
</body>
</html>