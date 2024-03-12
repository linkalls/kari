<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $originalUrl = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
  $customPath = filter_input(INPUT_POST, 'customPath', FILTER_SANITIZE_STRING);

  $shortUrl = substr(hash('sha256', $originalUrl), 0, 8);

  if ($customPath) {
    $shortUrl = $customPath;
  }

  // データベース内に短縮URLが既に存在するかチェックする
  $checkSql = "SELECT * FROM short_urls WHERE short_url = ?";
  $checkStmt = $conn->prepare($checkSql);
  $checkStmt->bind_param("s", $shortUrl);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows > 0) {
    // 短縮URLが既に存在する場合
    $message = "エラー: その短縮URLは既に存在します。";
  } else {
    // 短縮URLが存在しない場合、データベースに挿入する
    $sql = "INSERT INTO short_urls (short_url, original_url) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $shortUrl, $originalUrl);

    if ($stmt->execute()) {
      $message = "potetotown.cloudfree.jp/" . $shortUrl;
    } else {
      $message = "エラー: " . $sql . "<br>" . $conn->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>URL短縮サービス</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="font-sans bg-gradient-to-r from-blue-900 via-blue-700 to-blue-500 min-h-screen flex items-center justify-center text-white">
  <div class="bg-white p-6 rounded shadow-md text-black w-full max-w-md">
    <div class="flex justify-between items-center">
      <h1 class="text-3xl mb-4">URL短縮サービス</h1>
      <div>
        <a href="signup.php" class="inline-block p-2 rounded bg-blue-500 text-white mr-2">登録</a>
        <a href="login.php" class="inline-block p-2 rounded bg-blue-500 text-white">ログイン</a>
      </div>
    </div>
    <form id="url-form" class="flex flex-col" method="post">
      <div class="mb-4">
        <label for="url" class="block text-lg font-medium text-gray-700">URLを入力</label>
        <input type="text" name="url" id="url" placeholder="URLを入力" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-lg border-gray-300 rounded-md p-3 bg-gray-100">
      </div>
      <div class="mb-4">
        <label for="customPath" class="block text-lg font-medium text-gray-700">カスタムパス（オプション）</label>
        <input type="text" name="customPath" id="customPath" placeholder="カスタムパス（オプション）" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-lg border-gray-300 rounded-md p-3 bg-gray-100">
      </div>
      <button type="submit" class="p-2 rounded bg-indigo-500 text-white mt-2 hover:bg-indigo-700 transition duration-200">短縮URLを作成</button>
    </form>
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($message)): ?>
    <button onclick="copyToClipboard()" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md mt-4">コピー</button>
      <p id="copyMessage" class="mt-4"><?php echo $message; ?></p>
    <?php endif; ?>
  </div>
  <script src="urlsx.js"></script>
</body>
</html>