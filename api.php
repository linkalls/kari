<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    switch ($action) {

case 'signup':
   $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
   $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

   // ユーザー名が既に存在するか確認
   $sql = "SELECT * FROM users WHERE username = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("s", $username);
   $stmt->execute();
   $result = $stmt->get_result();

   if ($result->num_rows > 0) {
      // ユーザー名が既に存在する
      echo json_encode(["success" => false, "message" => "ユーザー名が既に存在します"]);
   } else {
     // ユーザー登録
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hashed_password);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // ユーザー登録成功

        // 自動ログインのために、新しく登録したユーザーのIDをセッションに保存
        $_SESSION["user_id"] = $stmt->insert_id;

        echo json_encode(["success" => true, "message" => "ユーザー登録成功"]);
    } else {
        echo json_encode(["success" => false, "message" => "ユーザー登録失敗"]);
    }
   }
    break;
        case 'login':
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
                    // ログイン成功
                    $_SESSION["user_id"] = $user['user_id'];
                    echo json_encode(["success" => true, "message" => "ログイン成功"]);
                } else {
                    // パスワードが間違っている
                    echo json_encode(["success" => false, "message" => "ユーザー名またはパスワードが間違っています"]);
                }
            } else {
                // ユーザー名が存在しない
                echo json_encode(["success" => false, "message" => "ユーザー名が存在しません"]);
            }
            break;

        case 'logout':
            session_start();
            session_destroy();
            echo json_encode(["success" => true, "message" => "ログアウト成功"]);
            break;

        case 'dashboard':
            session_start();
            if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] == 0) {
                echo json_encode(["success" => false, "message" => "ログインが必要です"]);
                exit;
            }

            $userId = $_SESSION["user_id"];
            $sql = "SELECT original_url, short_url, created_at, COUNT(a.accessed_at) as access_count
                    FROM short_urls u 
                    LEFT JOIN url_accesses a ON u.id = a.short_url_id
                    WHERE u.user_id = ? AND u.user_id != 0
                    GROUP BY u.original_url, u.short_url, u.created_at";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
            $stmt->execute();

            $result = $stmt->get_result();
            $urls = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            echo json_encode(["success" => true, "urls" => $urls]);
            break;

        case 'details':
            session_start();
            if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] == 0) {
                echo json_encode(["success" => false, "message" => "ログインが必要です"]);
                exit;
            }

            $shortUrl = filter_input(INPUT_POST, 'short_url', FILTER_SANITIZE_STRING);
            $userId = $_SESSION["user_id"];

            $sql = "SELECT u.original_url, u.short_url, u.created_at, COUNT(a.accessed_at) as access_count
                    FROM short_urls u 
                    LEFT JOIN url_accesses a ON u.id = a.short_url_id
                    WHERE u.short_url = ? AND u.user_id = ? AND u.user_id != 0
                    GROUP BY u.original_url, u.short_url, u.created_at";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $shortUrl, $userId);
            $stmt->execute();

            $result = $stmt->get_result();
            $url = $result->fetch_assoc();
            $stmt->close();

            if ($url) {
                echo json_encode(["success" => true, "url" => $url]);
            } else {
                echo json_encode(["success" => false, "message" => "URLが見つかりません"]);
            }
            break;

        default:
            echo json_encode(["success" => false, "message" => "無効なアクション"]);
            break;
    }
} else {
    echo json_encode(["success" => false, "message" => "POSTリクエストが必要です"]);
}
?>