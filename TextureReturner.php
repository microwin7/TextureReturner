<?php
#
# Скрипт отдачи текстур и их модификаций
#
# https://github.com/microwin7/TextureReturner
#
start();
class Constants
{
    const DEBUG = false; // Сохранение в файл debug.log !!! Не устанавливайте true навсегда и не забудьте после настройки удалить файл debug.log из папки
    const SKIN_PATH = "./skins/"; // Сюда вписать путь до skins/
    const CLOAK_PATH = "./cloaks/"; // Сюда вписать путь до cloaks/
    const REGEX_USERNAME = "/^\w{1,16}$/";
    const REGEX_UUIDv4 = "/^[a-f\d]{8}\-[a-f\d]{4}\-4[a-f\d]{3}\-[89ab][a-f\d]{4}\-[a-f\d]{12}$/i";
    const REGEX_UUIDv1 = "/^[a-f\d]{8}\-[a-f\d]{4}\-1[a-f\d]{3}\-[89ab][a-f\d]{4}\-[a-f\d]{12}$/i";
    const GIVE_DEFAULT = true; // Выдавать ли этим скриптом default скины и плащи, если упомянутые не найдены в папках
    const SKIN_DEFAULT = "iVBORw0KGgoAAAANSUhEUgAAAEAAAAAgCAMAAACVQ462AAAAWlBMVEVHcEwsHg51Ri9qQC+HVTgjIyNOLyK7inGrfWaWb1udZkj///9SPYmAUjaWX0FWScwoKCgAzMwAXl4AqKgAaGgwKHImIVtGOqU6MYkAf38AmpoAr68/Pz9ra2t3xPtNAAAAAXRSTlMAQObYZgAAAZJJREFUeNrUzLUBwDAUA9EPMsmw/7jhNljl9Xdy0J3t5CndmcOBT4Mw8/8P4pfB6sNg9yA892wQvwzSIr8f5JRzSeS7AaiptpxazUq8GPQB5uSe2DH644GTsDFsNrqB9CcDgOCAmffegWWwAExnBrljqowsFBuGYShY5oakgOXs/39zF6voDG9r+wLvTCVUcL+uV4m6uXG/L3Ut691697tgnZgJavinQHOB7DD8awmaLWEmaNuu7YGf6XcIITRm19P1ahbARCRGEc8x/UZ4CroXAQTVIGL0YySrREBADFGicS8XtG8CTS+IGU2F6EgSE34VNKoNz8348mzoXGDxpxkQBpg2bWobjgZSm+uiKDYH2BAO8C4YBmbgAjpq5jUl4yGJC46HQ7HJBfkeTAImIEmgmtpINi44JsHx+CKA/BTuArISXeBTR4AI5gK4C2JqRfPs0HNBkQnG8S4Yxw8IGoIZfXEBOW1D4YJDAdNSXgRevP+ylK6fGBCwsWywmA19EtBkJr8K2t4N5pnAVwH0jptsBp+2gUFj4tL5ywAAAABJRU5ErkJggg==";
    const CLOAK_DEFAULT = "iVBORw0KGgoAAAANSUhEUgAAAEAAAAAgAQMAAACYU+zHAAAAA1BMVEVHcEyC+tLSAAAAAXRSTlMAQObYZgAAAAxJREFUeAFjGAV4AQABIAABL3HDQQAAAABJRU5ErkJggg==";
}
class Occurrences
{
    public static $requiredUrl = null;
    public static $queries = null;
    public static $login = null;
    public static $method = null;

    function __construct()
    {
        self::requiredUrl();
        self::getQueries();
        self::getLogin();
        self::getMethod();
    }
    public static function requiredUrl(): string
    {
        if (self::$requiredUrl) return self::$requiredUrl;
        $requiredUrl = $_SERVER['QUERY_STRING'];
        !empty($requiredUrl) ?: responseTexture();
        return self::$requiredUrl = $requiredUrl;
    }
    public static function getQueries()
    {
        if (self::$queries) return self::$queries;
        $queries = array();
        parse_str(self::$requiredUrl, $queries);
        return self::$queries = $queries;
    }
    public static function getLogin(): string
    {
        if (self::$login) return self::$login;
        $login = self::$queries['login'] ?? responseTexture();
        (Check::regex_valid_username($login) || Check::regex_valid_uuid($login)) ?: responseTexture();
        !empty($login) ?: responseTexture();
        return self::$login = $login;
    }
    public static function getMethod(): string
    {
        if (self::$method) return self::$method;
        $method = self::$queries['method'] ?? responseTexture();
        !empty($method) ?: responseTexture();
        return self::$method = $method;
    }
}
class Check
{
    public static function skin($login)
    {
        $path = Check::ci_find_file(Constants::SKIN_PATH . $login . '.png');
        return $path ? file_get_contents($path) : (Constants::GIVE_DEFAULT ? base64_decode(Constants::SKIN_DEFAULT) : responseTexture());
    }
    public static function cloak($login)
    {
        $path = Check::ci_find_file(Constants::CLOAK_PATH . $login . '.png');
        return $path ? file_get_contents($path) : (Constants::GIVE_DEFAULT ? base64_decode(Constants::CLOAK_DEFAULT) : responseTexture());
    }
    public static function slim($data): bool
    {
        $image = imagecreatefromstring($data);
        $fraction = imagesx($image) / 8;
        $x = $fraction * 6.75;
        $y = $fraction * 2.5;
        $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        if ($rgba["alpha"] === 127)
            return true;
        else return false;
    }
    private static function ci_find_file($filename)
    {
        if (file_exists($filename)) return $filename;
        $directoryName = dirname($filename);
        $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
        $fileNameLowerCase = strtolower($filename);
        foreach ($fileArray as $file) {
            if (strtolower($file) == $fileNameLowerCase) {
                return $file;
            }
        }
        return false;
    }
    public static function regex_valid_username($var)
    {
        return (!is_null($var) && (preg_match(Constants::REGEX_USERNAME, $var, $varR)));
    }
    public static function regex_valid_uuid($var)
    {
        return (!is_null($var) && (preg_match(Constants::REGEX_UUIDv1, $var, $varR) ||
            preg_match(Constants::REGEX_UUIDv4, $var, $varR)));
    }
}
class Modifier
{
    public static function front($data)
    {
    }
    public static function back($data)
    {
    }
    public static function avatar($data)
    {
    }
    public static function cloak_resize($data)
    {
    }
}
function start()
{
    if (Constants::DEBUG) logs();
    $occurrences = new Occurrences();
    $login = $occurrences::$login;
    $method = $occurrences::$method;
    switch ($method) {
        case 'skin':
        case 'front':
        case 'back':
        case 'avatar':
            $data = Check::skin($login);
            switch ($method) {
                case 'skin':
                    responseTexture($data);
                    break;
                default:
                    Modifier::$method($data);
                    break;
            }
            break;
        case 'cloak':
        case 'cloak_resize':
            $data = Check::cloak($login);
            switch ($method) {
                case 'cloak':
                    responseTexture($data);
                    break;
                default:
                    Modifier::$method($data);
                    break;
            }
            break;
        default:
            responseTexture();
    }
}
// Постоянные вызовы этой функции без смысла сбивают с толку
// TODO Нужно переделать
function responseTexture($data = null)
{
    if ($data) {
        header("Content-type: image/png");
        die($data);
    } else {
        header("HTTP/1.0 404 Not Found");
        die;
    }
}
function logs()
{
    if (Constants::DEBUG) {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        debug_log('RequiredUrl', Occurrences::requiredUrl());
    }
}
function debug_log($what, $log)
{
    if (Constants::DEBUG) {
        file_put_contents("debug.log", date('d.m.Y H:i:s - ') . "[$what]: " . $log . "\n", FILE_APPEND);
    }
}
