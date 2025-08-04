<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if ($email) {
        file_put_contents("emails.txt", $email . PHP_EOL, FILE_APPEND);
        http_response_code(200);
        exit;
    }
}
http_response_code(400);
exit;