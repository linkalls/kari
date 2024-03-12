<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $originalUrl = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
    $customPath = filter_input(INPUT_POST, 'customPath', FILTER_SANITIZE_STRING);

    $shortUrl = substr(hash('sha256', $originalUrl), 0, 8);

    if ($customPath) {
        $shortUrl = $customPath;
    }

    $sql = "INSERT INTO short_urls (short_url, original_url) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $shortUrl, $originalUrl);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(array("short_url" => $shortUrl));
    } else {
        header('Content-Type: application/json');
        echo json_encode(array("error" => "Error: " . $sql . "<br>" . $conn->error));
    }
}
?>