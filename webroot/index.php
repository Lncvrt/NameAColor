<?php
include __DIR__ . "/config/captcha.php";

include __DIR__ . "/incl/db.php";

$conn = connectToDatabase();

$stmt = $conn->prepare("SELECT * FROM colors");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

usort($result, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

$lastInsertId = $_GET['highlight'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name A Color</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
    <style>
        body {
            font-family: "Lexend", sans-serif;
            background-color: #121212;
            color: #fff;
            text-align: center;
        }

        .named-color {
            display: inline-block;
            margin: 10px;
            background-color: #1e1e1e;
            padding: 10px;
            border-radius: 10px;
            width: 128px;
            word-wrap: break-word;
        }

        .named-color.highlight {
            border: 2px solid #006eff
        }

        .used-text {
            display: none;
            color: red;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usedColors = <?= json_encode(array_column($result, 'color')) ?>;
            const colorInput = document.querySelector('.color-picker');
            const element = document.querySelector('.used-text');
            const submit = document.querySelector('.submit-button');
            let captchaComplete = false;

            colorInput.value = '#<?= bin2hex(random_bytes(3)) ?>';

            const checkColor = () => {
                if (usedColors.includes(colorInput.value.substr(1))) {
                    element.style.display = 'block';
                    submit.disabled = true;
                } else {
                    element.style.display = 'none';
                    if (captchaComplete) {
                        submit.disabled = false;
                    }
                }
            };

            turnstile.ready(function () {
                turnstile.render(".cf-turnstile", {
                    sitekey: "<?= $sitekey ?>",
                    callback: function (token) {
                        captchaComplete = true;
                        submit.disabled = false;
                    },
                });
            });

            colorInput.addEventListener('input', checkColor);
            checkColor();
        });
    </script>
</head>
<body>
    <h1>Name A Color!</h1>
    <hr>
    <h2>Name your own color!</h2>
    <form action="upload.php" method="post">
        <p class="used-text">This color has already been named!</p>
        <label for="color">Color:</label>
        <input type="color" id="color" name="color" class="color-picker" required>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" maxlength="50" required>
        <button type="submit" class="submit-button" disabled>Name it!</button>
        <div class="cf-turnstile"></div>
    </form>
    <hr>
    <h2>Colors that were named</h2>
    <?php
    foreach ($result as $row) {
        echo "<div class=\"named-color" . ($lastInsertId != null && $lastInsertId == $row['id'] ? " highlight" : "") . "\">";
        echo "<div style=\"width: 128px; height: 128px; background: #" . $row['color'] . ";\"></div>";
        echo "<p>" . htmlspecialchars(base64_decode($row['name'])) . " &bull; #". $row["color"] ."</p>";
        echo "</div>";
    }
    ?>
</body>
</html>