<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
  $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

  // ユーザー名が既に存在するかチェック
  $check_sql = "SELECT * FROM users WHERE username = ?";
  $check_stmt = $conn->prepare($check_sql);
  $check_stmt->bind_param("s", $username);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    $error = "そのユーザー名は既に使われています";
  } else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hashed_password);

    // トランザクション開始
    $conn->begin_transaction();

    if ($stmt->execute()) {
      // コミット（トランザクションを確定）
      $conn->commit();
      $_SESSION['message'] = "サインアップに成功しました！ログインしてください！";
      header("Location: login.php");
      exit;
    } else {
      // ロールバック（トランザクションを取り消し）
      $conn->rollback();
      $error = "エラー: " . $sql . "<br>" . $conn->error;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>サインアップ</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="font-sans bg-gradient-to-r from-blue-900 via-blue-700 to-blue-500 min-h-screen flex items-center justify-center text-white">
  <div class="bg-white p-6 rounded shadow-md text-black w-full max-w-md">
    <h1 class="text-3xl mb-4">サインアップ</h1>
    <form action="signup.php" method="post" class="space-y-6">
      <div>
        <label for="username" class="sr-only">ユーザー名:</label>
        <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded dark:bg-gray-700 dark:text-white dark:border-gray-600" placeholder="ユーザー名" aria-label="ユーザー名">
      </div>
      <div>
        <label for="password" class="sr-only">パスワード:</label>
        <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded dark:bg-gray-700 dark:text-white dark:border-gray-600" placeholder="パスワード" aria-label="パスワード">
      </div>
      <div>
        <input type="submit" value="サインアップ" class="w-full px-3 py-2 text-white bg-blue-500 rounded hover:bg-blue-600" aria-label="サインアップ">
      </div>
    </form>
    <?php if (isset($error)): ?>
      <p class="text-red-500"><?php echo $error; ?></p>
    <?php endif; ?>
  </div>
</body>
</html>