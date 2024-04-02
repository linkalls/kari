<?php
session_start();
session_destroy();
session_start();
$_SESSION["flash_message"] = "ログアウトが完了しました。";
header("Location: /");
exit;
?>