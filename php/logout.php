<?php
session_start();
session_destroy();
session_start();
$_SESSION["message"] = "ログアウトが完了しました。";
header("Location: index.php");
exit;
?>