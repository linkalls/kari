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
                echo json_encode([
                    "success" => true,
                    "message" => "ログイン成功",
                    "user_id" => $user['user_id'] // ユーザーIDを含める
                ]);
            } else {
                // パスワードが間違っている
                echo json_encode(["success" => false, "message" => "ユーザー名またはパスワードが間違っています"]);
            }
        } else {
            // ユーザー名が存在しない
            echo json_encode(["success" => false, "message" => "ユーザー名が存在しません"]);
        }
        break;

                case 'create_short_url':
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
                        if (preg_match('/\\\\u([a-fA-F0-9]{4})/', $customPath)) {
                            echo json_encode(["success" => false, "message" => "ユニコードエスケープされた文字はカスタムパスで設定できません。"]);
                            exit;
                        }

                        $checkCustomPathSql = "SELECT * FROM short_urls WHERE short_url = ?";
                        $checkCustomPathStmt = $conn->prepare($checkCustomPathSql);
                        $checkCustomPathStmt->bind_param("s", $customPath);
                        $checkCustomPathStmt->execute();
                        $checkCustomPathResult = $checkCustomPathStmt->get_result();

                        if ($checkCustomPathResult->num_rows > 0) {
                            echo json_encode(["success" => false, "message" => "そのカスタムパスは既に存在します。"]);
                            exit;
                        } else {
                            $insertSql = "INSERT INTO short_urls (short_url, original_url, user_id) VALUES (?, ?, ?)";
                            $insertStmt = $conn->prepare($insertSql);
                            $insertStmt->bind_param("sss", $customPath, $originalUrl, $user_id);
                            if (!$insertStmt->execute()) {
                                echo json_encode(["success" => false, "message" => "SQLエラー: " . $insertStmt->error]);
                                exit;
                            }
                        }
                    }

                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows > 0) {
                        echo json_encode(["success" => false, "message" => "その短縮URLは既に存在します。"]);
                        exit;
                    } else {
                        $sql = "INSERT INTO short_urls (short_url, original_url, user_id) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sss", $shortUrl, $originalUrl, $user_id);

                        if ($stmt->execute()) {
                            $shortUrlMessage = $_SERVER['HTTP_HOST'] . "/" . $shortUrl;
                            echo json_encode(["success" => true, "message" => "短縮URLの生成が完了しました。", "shortUrl" => $shortUrlMessage]);
                            exit;
                        } else {
                            echo json_encode(["success" => false, "message" => "エラー: " . $sql . "<br>" . $conn->error]);
                            exit;
                        }
                    }
                    break;

                
            }
        } else {
            echo json_encode(["success" => false, "message" => "POSTリクエストが必要です"]);
        }
        ?>

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