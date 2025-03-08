<?php
include __DIR__ . "/config/captcha.php";

include __DIR__ . "/incl/db.php";

$conn = connectToDatabase();

$stmt = $conn->prepare("SELECT * FROM colors");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

usort($result, function($a, $b) {
    return strcmp($b['timestamp'], $a['timestamp']);
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

        .color {
            width: 128px;
            height: 128px;
            border-radius: 5px;
        }

        .used-text {
            display: none;
            color: red;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usedColors = <?= json_encode(array_column($result, 'color')) ?>;
            const usedNames = <?= json_encode(array_column($result, 'name')) ?>;
            const colorInput = document.querySelector('.color-picker');
            const nameInput = document.querySelector('.name-input');
            const usedColorText = document.querySelector('.used-color');
            const usedNameText = document.querySelector('.used-text');
            const colorValue = document.querySelector('.color-value');
            const submit = document.querySelector('.submit-button');
            let captchaComplete = false;

            colorInput.value = '#<?= bin2hex(random_bytes(3)) ?>';

            const checkColor = () => {
                colorValue.innerText = `Hex: ${colorInput.value}`;
                if (usedColors.includes(colorInput.value.substr(1))) {
                    usedColorText.style.display = 'block';
                    submit.disabled = true;
                } else {
                    usedColorText.style.display = 'none';
                    if (captchaComplete) {
                        submit.disabled = false;
                    }
                }
            };

            const checkName = () => {
                if (usedNames.includes(btoa(nameInput.value))) {
                    usedNameText.style.display = 'block';
                    submit.disabled = true;
                } else {
                    usedNameText.style.display = 'none';
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
            nameInput.addEventListener('input', checkName);
            checkColor();
            checkName();
        });
    </script>
</head>
<body>
    <h1>Name A Color!</h1>
    <hr>
    <h2>Name your own color!</h2>
    <form action="upload.php" method="post">
        <p class="used-color">This color has already been named!</p>
        <p class="used-text">This name has already been used!</p>
        <label for="color">Color:</label>
        <input type="color" id="color" name="color" class="color-picker" required>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" maxlength="50" class="name-input" required>
        <label class="color-value"></label>
        <button type="submit" class="submit-button" disabled>Name it!</button>
        <div class="cf-turnstile"></div>
    </form>
    <hr>
    <h2>Colors that were named</h2>
    <?php
    foreach ($result as $row) {
        echo "<div class=\"named-color" . ($lastInsertId != null && $lastInsertId == $row['id'] && ($row['timestamp'] > time() - 10) ? " highlight" : "") . "\">";
        echo "<div class=\"color\" style=\"background: #" . $row['color'] . ";\"></div>";
        echo "<p>" . htmlspecialchars(base64_decode($row['name'])) . " &bull; #". $row["color"] ."</p>";
        if ($row['hostname'] == hash('sha256', $_SERVER["HTTP_CF_CONNECTING_IP"])) {
            echo "<img src=\"assets/delete.svg\" alt=\"Delete\" style=\"cursor: pointer; width: 24px; height: 24px;\" onclick=\"window.location.href='delete.php?id=" . $row['id'] . "'\">";
        }
        echo "</div>";
    }
    ?>
</body>
</html>