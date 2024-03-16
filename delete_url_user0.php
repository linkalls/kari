<?php
include 'db.php';

// 現在の日付から30日前の日付を取得
$date = new DateTime();
$date->modify('-30 days');
$deleteDate = $date->format('Y-m-d H:i:s');

// user_idが0で、作成日が30日前以前のレコードを削除
$sql = "DELETE a FROM url_accesses a INNER JOIN short_urls u ON a.short_url_id = u.id WHERE u.user_id = 0 AND u.created_at <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $deleteDate);
$stmt->execute();
$stmt->close();

$sql = "DELETE FROM short_urls WHERE user_id = 0 AND created_at <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $deleteDate);
$result = $stmt->execute();
$stmt->close();

if ($result) {
  echo "30日以上前に作成されたユーザーIDが0のURLを削除しました。\n";
} else {
  echo "削除するURLはありませんでした。\n";
}
?>