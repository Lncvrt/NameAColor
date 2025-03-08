<?php
include __DIR__ . "/../config/captcha.php";
include __DIR__ . "/../incl/db.php";

$hostname = hash('sha256', $_SERVER["HTTP_CF_CONNECTING_IP"]);

$conn = connectToDatabase();

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM colors WHERE id = :id AND hostname = :hostname");
$stmt->bindParam(':id', $id);
$stmt->bindParam(':hostname', $hostname);
$stmt->execute();

header("Location: /");