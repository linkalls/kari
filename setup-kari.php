<?php
// setup-kari.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // フォームからデータベース情報を取得
  $servername = $_POST['servername'];
  $username = $_POST['username'];
  $password = $_POST['password'];
  $dbname = $_POST['dbname'];

  // データベース接続を試みる
  $conn = new mysqli($servername, $username, $password);

  if ($conn->connect_error) {
    die("接続失敗: " . $conn->connect_error);
  }

  // データベースが存在しない場合は作成する
  $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
  if ($conn->query($sql) !== TRUE) {
    die("データベースの作成エラー: " . $conn->error . "<br>");
  }

  // データベースに接続する
  $conn = new mysqli($servername, $username, $password, $dbname);

  if ($conn->connect_error) {
    die("接続失敗: " . $conn->connect_error);
  }

  // 必要なテーブルを作成する
  $sql = "
  CREATE TABLE IF NOT EXISTS users (
    password VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    whatuser_id INT NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id)
);

  CREATE TABLE IF NOT EXISTS short_urls (
    id INT AUTO_INCREMENT NOT NULL,
    short_url VARCHAR(255) NOT NULL,
    original_url VARCHAR(2048) NOT NULL,
    user_id VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
  );

  CREATE TABLE IF NOT EXISTS url_accesses (
    id INT AUTO_INCREMENT NOT NULL,
    short_url_id INT,
    accessed_at DATETIME NOT NULL,
    referrer VARCHAR(2048),
    client_ip VARCHAR(45),
    user_agent VARCHAR(255),
    PRIMARY KEY (id),
    FOREIGN KEY (short_url_id) REFERENCES short_urls(id)
  );
  ";

  if ($conn->multi_query($sql) !== TRUE) {
    die("テーブルの作成エラー: " . $conn->error . "<br>");
  }

  // 全てのクエリが実行されるまで待つ
  while ($conn->more_results() && $conn->next_result()) {
    // 結果セットをフリーにする
    $result = $conn->use_result();
    if ($result instanceof mysqli_result) {
      $result->free();
    }
  }

  // short_urlsテーブルにcreated_at列を追加する
  $sql = "ALTER TABLE short_urls ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
  if ($conn->query($sql) !== TRUE) {
    die("テーブルの更新エラー: " . $conn->error . "<br>");
  }

  // データベース接続を閉じる
  $conn->close();

  // 設定ファイルを生成する
  $configContent = "<?php
  \$servername = \"$servername\";
  \$username = \"$username\";
  \$password = \"$password\";
  \$dbname = \"$dbname\";

  \$conn = new mysqli(\$servername, \$username, \$password, \$dbname);

  if (\$conn->connect_error) {
    die(\"接続失敗: \" . \$conn->connect_error);
  }

  // 文字セットを設定する
  mysqli_set_charset(\$conn, \"utf8\");
  ";

  file_put_contents('db.php', $configContent);
  // 自身のファイルを削除する
  unlink(__FILE__);
  $_SESSION['flash_message'] = 'データベースの設定が成功しました。';
   // 設定が完了したら/にリダイレクト
   header('Location: /');
   exit;
   
}
?>


<!DOCTYPE html>
<html>
<head>
 <title>設定 Kari</title>
 <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
 <div class="container mx-auto px-4 py-5">
    <form action="setup-kari.php" method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="servername">サーバー名(localhostとかのdbのサーバー名):</label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="servername" name="servername" type="text" required>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">ユーザー名(dbのユーザー名):</label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" name="username" type="text" required>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">パスワード(db_userのパスワード):</label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" required>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="dbname">データベース名:</label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="dbname" name="dbname" type="text" required>
      </div>
      <div class="flex items-center justify-between">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">設定</button>
      </div>
    </form>
 </div>
</body>
</html>