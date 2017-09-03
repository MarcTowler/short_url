<?php
/**
 * submit.php - short_url
 *
 * @author    Marc Towler <marc.towler@designdeveloprealize.com>
 *
 * @copyright 2017 - Design Develop Realize
 */

if(array_key_exists('submit', $_POST))
{
    require_once "include/config.php";
    require_once "include/ShortUrl.php";
    if ($_SERVER["REQUEST_METHOD"] != "POST" || empty($_POST["url"])) {
        header("Location: shorten.html");
        exit;
    }
    try {
        $pdo = new PDO(DB_PDODRIVER . ":host=" . DB_HOST . ";dbname=" . DB_DATABASE,
            DB_USERNAME, DB_PASSWORD);
    }
    catch (\PDOException $e) {
        header("Location: error.html");
        exit;
    }
    $shortUrl = new ShortUrl($pdo);
    try {
        $code = $shortUrl->urlToShortCode($_POST["url"]);
    }
    catch (\Exception $e) {
        header("Location: error.html");
        exit;
    }
    $url = SHORTURL_PREFIX . $code;
?>
<html>
 <head>
  <title>URL Shortener</title>
 </head>
 <body>
  <p><strong>Short URL:</strong> <a href="$url">$url</a></p>
 </body>
</html>
<?php
} else {
?>
<html>
<head>
    <title>URL Shortener</title>
</head>
<body>
<form action="submit.php" method="post">
    <label for="url">Enter URL (http://example.com):</label>
    <input type="text" name="url" id="url">
    <input type="submit" value="Shorten">
</form>
</body>
</html>
<?php
}