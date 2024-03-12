<?php
include 'db.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $originalUrl = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
  $customPath = filter_input(INPUT_POST, 'customPath', FILTER_SANITIZE_STRING);

  $shortUrl = substr(hash('sha256', $originalUrl), 0, 8);
  
  // Check if user_id is set in the session
  $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0;

  if ($customPath) {
    $shortUrl = $customPath;
  }

  // データベース内に同じユーザーが同じ短縮URLを作成しようとしているかチェックする
  if ($user_id != 0) {
    $checkSql = "SELECT * FROM short_urls WHERE short_url = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $shortUrl, $user_id);
  } else {
    $checkSql = "SELECT * FROM short_urls WHERE short_url = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $shortUrl);
  }
  
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows > 0) {
    // 同じユーザーが同じ短縮URLを作成しようとしている場合
    $_SESSION['error'] = "エラー: その短縮URLは既に存在します。";
  } else {
    // 短縮URLが存在しない場合、データベースに挿入する
    $sql = "INSERT INTO short_urls (short_url, original_url, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $shortUrl, $originalUrl, $user_id);
  
    if ($stmt->execute()) {
      $shortUrlMessage = "your_domain.com/" . $shortUrl;
    } else {
      $shortUrlMessage = "エラー: " . $sql . "<br>" . $conn->error;
    }
  }
}

// メッセージを表示する
if (isset($_SESSION['message'])) {
  echo $_SESSION['message'];
  // メッセージを削除する
  unset($_SESSION['message']);
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
    <?php if (isset($_SESSION["message"])): ?>
    <div class="bg-green-500 text-white p-4 rounded-md mb-4">
      <?php 
      echo $_SESSION["message"]; 
      unset($_SESSION["message"]);
      ?>
    </div>
    <?php endif; ?>
    <div class="flex justify-between items-center">
      <h1 class="text-3xl mb-4">URL短縮サービス</h1>
      <div>
        <?php if (isset($_SESSION["user_id"])): ?>
          <a href="dashboard.php" class="inline-block p-2 rounded bg-blue-500 text-white mr-2">ダッシュボード</a>
          <a href="logout.php" class="inline-block p-2 rounded bg-blue-500 text-white">サインアウト</a>
        <?php else: ?>
          <a href="signup.php" class="inline-block p-2 rounded bg-blue-500 text-white mr-2">登録</a>
          <a href="login.php" class="inline-block p-2 rounded bg-blue-500 text-white">ログイン</a>
        <?php endif; ?>
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

  

   <!-- 短縮URLを表示する部分 -->
<?php if (isset($shortUrlMessage)): ?>
<div class="bg-green-500 text-white p-4 rounded-md mb-4 mt-4">
  <?php 
  echo $shortUrlMessage; ?>
  <button id="copyButton" class="p-2 rounded bg-indigo-500 text-white mt-2 hover:bg-indigo-700 transition duration-200">コピー</button>
</div>
<div id="copyMessage"></div>
<?php endif; ?>
  </div>
  <!-- JavaScriptコードを追加 -->
<script>
document.getElementById('copyButton').addEventListener('click', function(event) {
  // ボタンのデフォルトの動作をキャンセル
  event.preventDefault();

  var text = "<?php echo $shortUrlMessage; ?>";
  navigator.clipboard.writeText(text).then(function() {
    document.getElementById('copyMessage').textContent = 'コピーされました';
  }, function(err) {
    console.error('Could not copy text: ', err);
  });
});
</script>
  <script src="urlsx.js"></script>
</body>
</html>