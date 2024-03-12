<?php
include 'db.php';
session_start(); // セッションを開始

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
  $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

  $sql = "SELECT * FROM users WHERE username = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION["user_id"] = $user['user_id']; // ユーザーIDをセッションに保存
      // ユーザーをダッシュボードにリダイレクト
      header("Location: /dashboard.php");
      exit;
    } else {
      $error = "ユーザー名またはパスワードが無効です";
    }
  } else {
    $error = "ユーザー名またはパスワードが無効です";
  }
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="font-sans bg-gradient-to-r from-blue-900 via-blue-700 to-blue-500 min-h-screen flex items-center justify-center text-white">
  <div class="bg-white p-8 rounded shadow-md text-black w-full max-w-md mx-4">
    <h1 class="text-3xl mb-4">ログイン</h1>
    <?php if (isset($_SESSION['message'])): ?>
      <div class="bg-green-500 text-white px-4 py-2 rounded">
        <?php 
        echo $_SESSION['message']; 
        unset($_SESSION['message']);
        ?>
      </div>
    <?php endif; ?>
    <form action="login.php" method="post" class="space-y-6">
      <div>
        <label for="username" class="sr-only">ユーザー名:</label>
        <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded dark:bg-gray-700 dark:text-white dark:border-gray-600" placeholder="ユーザー名" aria-label="ユーザー名">
      </div>
      <div>
        <label for="password" class="sr-only">パスワード:</label>
        <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded dark:bg-gray-700 dark:text-white dark:border-gray-600" placeholder="パスワード" aria-label="パスワード">
      </div>
      <div>
        <input type="submit" value="ログイン" class="w-full px-3 py-2 text-white bg-blue-500 rounded hover:bg-blue-600" aria-label="ログイン">
      </div>
    </form>
    <?php if (isset($error)): ?>
      <p class="text-red-500"><?php echo $error; ?></p>
    <?php endif; ?>
  </div>
</body>
</html>