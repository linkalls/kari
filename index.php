<?php
include 'db.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $originalUrl = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
  $customPath = filter_input(INPUT_POST, 'customPath', FILTER_SANITIZE_STRING);

  if (!preg_match("~^(?:f|ht)tps?://~i", $originalUrl)) {
    $originalUrl = "http://" . $originalUrl;
  }

  $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : '0';
  $uniqueValue = microtime();

  if ($user_id != '0') {
    $shortUrl = substr(hash('sha256', $originalUrl . $user_id . $uniqueValue), 0, 8);
    $checkSql = "SELECT * FROM short_urls WHERE short_url = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $shortUrl, $user_id);
  } else {
    $shortUrl = substr(hash('sha256', $originalUrl . $uniqueValue), 0, 8);
    $checkSql = "SELECT * FROM short_urls WHERE short_url = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $shortUrl);
  }

  if ($customPath) {
    $shortUrl = $customPath;
    $checkCustomPathSql = "SELECT * FROM short_urls WHERE short_url = ?";
    $checkCustomPathStmt = $conn->prepare($checkCustomPathSql);
    $checkCustomPathStmt->bind_param("s", $shortUrl);
    $checkCustomPathStmt->execute();
    $checkCustomPathResult = $checkCustomPathStmt->get_result();

    if ($checkCustomPathResult->num_rows > 0) {
      $_SESSION['error'] = "エラー: そのカスタムパスは既に存在します。";
      header("Location: index.php");
      exit;
    }
  }

  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows > 0) {
    $_SESSION['error'] = "エラー: その短縮URLは既に存在します。";
    header("Location: index.php");
    exit;
  } else {
    $sql = "INSERT INTO short_urls (short_url, original_url, user_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $shortUrl, $originalUrl, $user_id);
  
    if ($stmt->execute()) {
      $shortUrlMessage = "your_domain.com/" . $shortUrl;
      $_SESSION['flash_message'] = "短縮URLの生成が完了しました。";
      $_SESSION['shortUrlMessage'] = $shortUrlMessage;
      header("Location: index.php"); // リダイレクトを追加
      exit;
  } else {
      $shortUrlMessage = "エラー: " . $sql . "<br>" . $conn->error;
      $_SESSION['error'] = $shortUrlMessage;
      header("Location: index.php"); // リダイレクトを追加
      exit;
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
  <style>
    #flash-message, #error-message {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: none;
    }
    #close-button, #error-close-button {
      cursor: pointer;
      position: absolute;
      right: 10px;
      top: 10px;
    }
  </style>
</head>
<body class="font-sans bg-gradient-to-r from-blue-900 via-blue-700 to-blue-500 min-h-screen flex items-center justify-center text-white">
  <div class="w-full max-w-md p-4 bg-white rounded-md shadow-md">
    <div class="flex justify-between items-center">
      <h1 class="text-3xl mb-4 text-black p-2">URL短縮サービス</h1>
      <div>
        <?php if (isset($_SESSION["user_id"])): ?>
          <a href="dashboard.php" class="inline-block p-2 rounded bg-blue-500 text-white mr-2 mb-2">ダッシュボード</a>
          <a href="logout.php" class="inline-block p-2 rounded bg-blue-500 text-white mb-2">サインアウト</a>
        <?php else: ?>
          <a href="signup.php" class="inline-block p-2 rounded bg-blue-500 text-white mr-2">登録</a>
          <a href="login.php" class="inline-block p-2 rounded bg-blue-500 text-white">ログイン</a>
        <?php endif; ?>
      </div>
    </div>
    <form id="url-form" class="flex flex-col" method="post">
      <div class="mb-4">
        <label for="url" class="block text-lg font-medium text-gray-700">URLを入力</label>
        <input type="text" name="url" id="url" placeholder="URLを入力" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-lg border-gray-300 rounded-md p-3 bg-gray-100 text-gray" style="color: black;" value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : ''; ?>">
      </div>
      <div class="mb-4">
        <label for="customPath" class="block text-lg font-medium text-gray-700">カスタムパス（オプション）</label>
        <input type="text" name="customPath" id="customPath" placeholder="カスタムパス（オプション）" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-lg border-gray-300 rounded-md p-3 bg-gray-100" style="color: black;" value="<?php echo isset($_POST['customPath']) ? htmlspecialchars($_POST['customPath']) : ''; ?>">
      </div>
      <button type="submit" class="p-2 rounded bg-indigo-500 text-white mt-2 hover:bg-indigo-700 transition duration-200">短縮URLを作成</button>
    </form>

    <!-- フラッシュメッセージ -->
<?php if (isset($_SESSION['flash_message'])): ?>
  <div id="flash-message" class="bg-green-500 text-white p-4 rounded-md mb-4 mt-4 relative">
    <?php 
    echo $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // フラッシュメッセージを表示した後にセッション変数をクリア
    ?>
    <span id="close-button" class="absolute top-0 right-0 p-4">X</span>
  </div>
  <script>
    document.getElementById('flash-message').style.display = 'block';
    document.getElementById('close-button').addEventListener('click', function() {
      document.getElementById('flash-message').style.display = 'none';
    });
  </script>
<?php endif; ?>

    <!-- エラーメッセージ -->
    <?php if (isset($_SESSION['error'])): ?>
      <div id="error-message" class="bg-red-500 text-white p-4 rounded-md mb-4 mt-4 relative">
        <?php 
        echo $_SESSION['error'];
        ?>
        <span id="error-close-button" class="absolute top-0 right-0 p-4">X</span>
      </div>
      <script>
        document.getElementById('error-message').style.display = 'block';
        document.getElementById('error-close-button').addEventListener('click', function() {
          document.getElementById('error-message').style.display = 'none';
        });
      </script>
    <?php 
    unset($_SESSION['error']); // エラーメッセージを表示した後に$_SESSION['error']を削除
    endif; 
    ?>

<!-- コピー機能 -->
<?php if (isset($_SESSION['shortUrlMessage'])): ?>
  <div class="flex items-center mt-2">
    <input type="text" readonly class="mr-2 p-2 border border-gray-300 rounded" style="color: black;" value="<?php echo $_SESSION['shortUrlMessage']; ?>">
    <button id="copyButton" class="p-2 rounded bg-indigo-500 text-white hover:bg-indigo-700 transition duration-200">コピー</button>
  </div>
  <div id="copyMessage"></div>
  <script>
    document.getElementById('copyButton').addEventListener('click', function(event) {
      event.preventDefault();
      var text = "<?php echo $_SESSION['shortUrlMessage']; ?>";
      navigator.clipboard.writeText(text).then(function() {
        document.getElementById('copyMessage').textContent = 'コピーされました';
        $_SESSION['flash_message'] = "コピーしました。";
        header("Location: index.php");
        exit;
      }, function(err) {
        console.error('Could not copy text: ', err);
      });
    });
  </script>
<?php 
unset($_SESSION['shortUrlMessage']); // 短縮URLを表示した後に$_SESSION['shortUrlMessage']を削除
  endif; 
?>
  </div>
  <script src="urlsx.js"></script>
</body>
</html> 