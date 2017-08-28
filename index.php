<?php
include "include/config.php";
include "include/ShortUrl.php";

try {
    $pdo = new PDO(DB_PDODRIVER . ":host=" . DB_HOST .
        ";dbname=" . DB_DATABASE,
        DB_USERNAME, DB_PASSWORD);
}
catch (PDOException $e) {
    trigger_error("Error: Failed to establish connection to database.");
    exit;
}

$shortUrl = new ShortUrl($pdo);
try {
    $code = $shortUrl->urlToShortCode($_POST["url"]);
    printf('<p><strong>Short URL:</strong> <a href="%s">%1$s</a></p>',
        SHORTURL_PREFIX . $code);
    exit;
}
catch (Exception $e) {
    // log exception and then redirect to error page.
    header("Location: /error");
    exit;
}