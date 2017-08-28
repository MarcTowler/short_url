<?php
/**
 * config.php - short_url
 *
 * @author    Marc Towler <marc.towler@designdeveloprealize.com>
 *
 * @copyright 2017 - Design Develop Realize
 */

class ShortUrl
{
    protected static $chars = "123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ";
    protected static $table = "short_urls";
    protected static $checkUrlExists = true;

    protected $pdo;
    protected $timestamp;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->timestamp = $_SERVER["REQUEST_TIME"];
    }

    public function urlToShortCode($url)
    {
        if(empty($url))
        {
            throw new Exception("No URL was supplied.");
        }

        if($this->validateUrlFormat($url) == false)
        {
            throw new Exception(
                "URL does not have a valid format.");
        }

        if(self::$checkUrlExists)
        {
            if(!$this->verifyUrlExists($url))
            {
                throw new Exception(
                    "URL does not appear to exist.");
            }
        }

        $shortCode = $this->urlExistsInDb($url);
        if($shortCode == false)
        {
            $shortCode = $this->createShortCode($url);
        }

        return $shortCode;
    }

    protected function validateUrlFormat($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL,
            FILTER_FLAG_HOST_REQUIRED);
    }

    protected function verifyUrlExists($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404);
    }

    protected function urlExistsInDb($url)
    {
        $query = "SELECT short_code FROM " . self::$table .
            " WHERE long_url = :long_url LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["short_code"];
    }

    protected function createShortCode($url)
    {
        $id = $this->insertUrlInDb($url);
        $shortCode = $this->convertIntToShortCode($id);
        $this->insertShortCodeInDb($id, $shortCode);
        return $shortCode;
    }

    protected function insertUrlInDb($url)
    {
        $query = "INSERT INTO " . self::$table .
            " (long_url, date_created) " .
            " VALUES (:long_url, :timestamp)";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url,
            "timestamp" => $this->timestamp
        );
        $stmnt->execute($params);

        return $this->pdo->lastInsertId();
    }

    protected function convertIntToShortCode($id)
    {
        $id = intval($id);
        if($id < 1)
        {
            throw new Exception(
                "The ID is not a valid integer");
        }

        $length = strlen(self::$chars);
        // make sure length of available characters is at
        // least a reasonable minimum - there should be at
        // least 10 characters
        if($length < 10)
        {
            throw new Exception("Length of chars is too small");
        }

        $code = "";
        while($id > $length - 1)
        {
            // determine the value of the next higher character
            // in the short code should be and prepend
            $code = self::$chars[fmod($id, $length)] .
                $code;
            // reset $id to remaining value to be converted
            $id = floor($id / $length);
        }

        // remaining value of $id is less than the length of
        // self::$chars
        $code = self::$chars[$id] . $code;

        return $code;
    }

    protected function insertShortCodeInDb($id, $code)
    {
        if($id == null || $code == null)
        {
            throw new Exception("Input parameter(s) invalid.");
        }
        $query = "UPDATE " . self::$table .
            " SET short_code = :short_code WHERE id = :id";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "short_code" => $code,
            "id" => $id
        );
        $stmnt->execute($params);

        if($stmnt->rowCount() < 1)
        {
            throw new Exception(
                "Row was not updated with short code.");
        }

        return true;
    }

    public function shortCodeToUrl($code, $increment = true) {
        if (empty($code)) {
            throw new Exception("No short code was supplied.");
        }

        if ($this->validateShortCode($code) == false) {
            throw new Exception(
                "Short code does not have a valid format.");
        }

        $urlRow = $this->getUrlFromDb($code);
        if (empty($urlRow)) {
            throw new Exception(
                "Short code does not appear to exist.");
        }

        if ($increment == true) {
            $this->incrementCounter($urlRow["id"]);
        }

        return $urlRow["long_url"];
    }

    protected function validateShortCode($code) {
        return preg_match("|[" . self::$chars . "]+|", $code);
    }

    protected function getUrlFromDb($code) {
        $query = "SELECT id, long_url FROM " . self::$table .
            " WHERE short_code = :short_code LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params=array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result;
    }

    protected function incrementCounter($id) {
        $query = "UPDATE " . self::$table .
            " SET counter = counter + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);
    }
}