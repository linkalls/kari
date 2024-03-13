<?php
session_start();

if (isset($_SESSION["user_id"])) {
  echo "User ID is set: ";
  var_dump($_SESSION["user_id"]);
} else {
  echo "User ID is not set.";
}
?>