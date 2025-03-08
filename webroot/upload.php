<?php
include __DIR__ . "/config/captcha.php";
include __DIR__ . "/incl/db.php";

$time = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
    $token = $_POST['cf-turnstile-response'] ?? '';
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $secretkey,
        'response' => $token,
        'remoteip' => $ip,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $result = curl_exec($ch);
    curl_close($ch);

    if ($result !== false) {
        $outcome = json_decode($result, true);

        if (!isset($outcome['success']) || !$outcome['success']) {
            echo "Invalid Captcha";
            exit;
        }
    } else {
        echo "Couldn't validate Captcha";
        exit;
    }
    
    $conn = connectToDatabase();

    $color = $_POST['color'];
    $name = base64_encode($_POST['name'] ?? '');

    $color = ltrim($color, '#');
    if (!preg_match('/^[a-f0-9]{6}$/i', $color)) {
        echo "Invalid color";
        exit;
    }

    if (strlen($name) < 3) {
        echo "Name too short";
        exit;
    }

    if (strlen($name) > 50) {
        echo "Name too long";
        exit;
    }

    $stmt = $conn->prepare("SELECT name FROM colors WHERE color = :color");
    $stmt->bindParam(':color', $color);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "Color already taken";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO colors (color, name, timestamp) VALUES (:color, :name, :time)");
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':time', $time);
    $stmt->execute();
    $lastInsertId = $conn->lastInsertId();
    header("Location: /?highlight=$lastInsertId");
    exit;
}

header("Location: /");