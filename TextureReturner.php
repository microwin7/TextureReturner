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
    const CACHE_FOLDER = "./TextureReturner_cache/"; // mkdir -p ./TextureReturner_cache/front && mkdir -p ./TextureReturner_cache/back && mkdir -p ./TextureReturner_cache/avatar && mkdir -p ./TextureReturner_cache/cloak_resize && chown -R www-data:www-data ./TextureReturner_cache/
    const REGEX_USERNAME = "\w{1,16}$";
    const REGEX_UUIDv1_AND_v4 = "[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}";
    const GIVE_DEFAULT = true; // Устанавливать ли default скин и плащ, если не найден по $login
    const SKIN_DEFAULT = "iVBORw0KGgoAAAANSUhEUgAAAEAAAAAgCAMAAACVQ462AAAAWlBMVEVHcEwsHg51Ri9qQC+HVTgjIyNOLyK7inGrfWaWb1udZkj///9SPYmAUjaWX0FWScwoKCgAzMwAXl4AqKgAaGgwKHImIVtGOqU6MYkAf38AmpoAr68/Pz9ra2t3xPtNAAAAAXRSTlMAQObYZgAAAZJJREFUeNrUzLUBwDAUA9EPMsmw/7jhNljl9Xdy0J3t5CndmcOBT4Mw8/8P4pfB6sNg9yA892wQvwzSIr8f5JRzSeS7AaiptpxazUq8GPQB5uSe2DH644GTsDFsNrqB9CcDgOCAmffegWWwAExnBrljqowsFBuGYShY5oakgOXs/39zF6voDG9r+wLvTCVUcL+uV4m6uXG/L3Ut691697tgnZgJavinQHOB7DD8awmaLWEmaNuu7YGf6XcIITRm19P1ahbARCRGEc8x/UZ4CroXAQTVIGL0YySrREBADFGicS8XtG8CTS+IGU2F6EgSE34VNKoNz8348mzoXGDxpxkQBpg2bWobjgZSm+uiKDYH2BAO8C4YBmbgAjpq5jUl4yGJC46HQ7HJBfkeTAImIEmgmtpINi44JsHx+CKA/BTuArISXeBTR4AI5gK4C2JqRfPs0HNBkQnG8S4Yxw8IGoIZfXEBOW1D4YJDAdNSXgRevP+ylK6fGBCwsWywmA19EtBkJr8K2t4N5pnAVwH0jptsBp+2gUFj4tL5ywAAAABJRU5ErkJggg==";
    const CLOAK_DEFAULT = "iVBORw0KGgoAAAANSUhEUgAAAEAAAAAgAQMAAACYU+zHAAAAA1BMVEVHcEyC+tLSAAAAAXRSTlMAQObYZgAAAAxJREFUeAFjGAV4AQABIAABL3HDQQAAAABJRU5ErkJggg==";
    const AVATAR_CANVAS = 80; // Ширина и высота для метода avatar. Если будут проблемы с HD скинами с шириной 1024, установить значение на 128. Принято 80, как максимум для Embed Object thumbnail? который отображается по факту
    const BLOCK_CANVAS = 128; // Ширина блока для таких методов как front, back
    const CLOAK_CANVAS = 16; // Умножится на минимальную ширину в 22 пикселя
    const BOUND_WIDTH_CANVAS = 512; // Предел ширины, для ограничения нагрузки вызываемого параметра size. Действует только на front, back, avatar, cloak_resize == элементы работающие с canvas полем и преобразованием размера
    const IMAGE_CACHE_TIME = 60; // Кеширование front, back, avatar, cloak_resize в секундах

    public static function getSkin($login)
    {
        $filename = Utils::ci_find_file(self::SKIN_PATH . $login . '.png');
        return $filename ? file_get_contents($filename) : (self::GIVE_DEFAULT ? base64_decode(self::SKIN_DEFAULT) : responseTexture());
    }
    public static function getCloak($login)
    {
        $filename = Utils::ci_find_file(self::CLOAK_PATH . $login . '.png');
        return $filename ? file_get_contents($filename) : (self::GIVE_DEFAULT ? base64_decode(self::CLOAK_DEFAULT) : responseTexture());
    }
}
class Occurrences
{
    public static $requiredUrl = null;
    public static $queries = null;
    public static $login = null;
    public static $method = null;
    public static $size = null;

    function __construct()
    {
        self::requiredUrl();
        self::getQueries();
        self::getLogin();
        self::getMethod();
        self::getSize(self::$method);
    }
    public static function requiredUrl(): string
    {
        if (self::$requiredUrl == null) {
            $requiredUrl = $_SERVER['QUERY_STRING'] ?? null;
            !empty($requiredUrl) ?: responseTexture();
            return self::$requiredUrl = $requiredUrl;
        } else return self::$requiredUrl;
    }
    public static function getQueries()
    {
        if (self::$queries == null) {
            $queries = array();
            parse_str(self::$requiredUrl, $queries);
            return self::$queries = $queries;
        } else return self::$queries;
    }
    public static function getLogin(): string
    {
        if (self::$login == null) {
            $login = self::$queries['login'] ?? responseTexture();
            (Check::regex_valid_username($login) || Check::regex_valid_uuid($login)) ?: responseTexture();
            !empty($login) ?: responseTexture();
            return self::$login = $login;
        } else return self::$login;
    }
    public static function getMethod()
    {
        if (self::$method == null) {
            $method = self::$queries['method'] ?? null;
            !empty($method) ?: null;
            return self::$method = $method;
        } else return self::$method;
    }
    public static function getSize($method)
    {
        if (self::$size == null) {
            switch ($method) {
                case 'avatar':
                    $size = self::$queries['size'] ?? null;
                    !empty($size) ? $size : $size = Constants::AVATAR_CANVAS;
                    break;
                case 'cloak_resize':
                    $size = self::$queries['size'] ?? null;
                    !empty($size) ? $size : $size = Constants::CLOAK_CANVAS;
                    break;
                default:
                    $size = self::$queries['size'] ?? null;
                    !empty($size) ? $size : $size = Constants::BLOCK_CANVAS;
                    break;
            }
            if($size > Constants::BOUND_WIDTH_CANVAS) responseTexture();
            return self::$size = $size;
        } else return self::$size;
    }
}
class Check
{
    public static function skin($login)
    {
        $data = Constants::getSkin($login);
        [$image, $fraction] = Utils::pre_calculation($data);
        return [$image, $fraction, self::slim($image, $fraction)];
    }
    public static function slim($image, $fraction): bool
    {
        $x = $fraction * 6.75;
        $y = $fraction * 2.5;
        $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        if ($rgba["alpha"] === 127)
            return true;
        else return false;
    }
    public static function regex_valid_username($var)
    {
        if (!is_null($var) && (preg_match("/^" . Constants::REGEX_USERNAME . "/", $var, $varR)))
            return true;
    }
    public static function regex_valid_uuid($var)
    {
        return (!is_null($var) && preg_match("/" . Constants::REGEX_UUIDv1_AND_v4 . "/", $var, $varR));
    }
    public static function cacheValid($filename, $size)
    {
        if (!file_exists($filename)) return false;
        $time = filemtime($filename);
        if ($size != getimagesize($filename)) return false;
        if ($time <= time() - 1 * Constants::IMAGE_CACHE_TIME) return false;
        return true;
    }
}
class Utils
{
    public static function ci_find_file($filename)
    {
        if (file_exists($filename))
            return $filename;
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
    public static function pre_calculation($data)
    {
        $image = imagecreatefromstring($data);
        $fraction = imagesx($image) / 8;
        return [$image, $fraction];
    }
    public static function create_canvas_transparent($width, $height)
    {
        ini_set('gd.png_ignore_warning', 0); //отключение отладочной информации
        $canvas = imagecreatetruecolor($width, $height);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagesavealpha($canvas, TRUE);
        return $canvas;
    }
    public static function saveCacheFile($login, $canvas, $method)
    {
        $filename = Constants::CACHE_FOLDER . strtolower($method) . '/' . strtolower($login) . '.png';
        imagepng($canvas, $filename, 9);
    }
    public static function loadCacheFile($filename)
    {
        return file_get_contents($filename);
    }
    public static function removeCacheFiles($method)
    {
        foreach (glob(Constants::CACHE_FOLDER . strtolower($method) . '/*', GLOB_NOSORT) as $file) {

            if (time() - lstat($file)['ctime'] > Constants::IMAGE_CACHE_TIME * 2) {
                unlink($file);
            }
        }
    }
}
class Modifier
{
    public static function front($data, $size)
    {
        // Создано пока что только для скинов по шаблону 64x32
        [$image, $fraction] = $data;
        $canvas = Utils::create_canvas_transparent($size, $size * 2);
        $f_part = $fraction / 2;
        $canvas_front = Utils::create_canvas_transparent($fraction * 2, $fraction * 4);
        $canvas_arm = Utils::create_canvas_transparent($f_part, $f_part * 3);
        $canvas_leg = $canvas_arm;
        // Head
        imagecopy($canvas_front, $image, $f_part, 0, $fraction, $fraction, $fraction, $fraction);
        //Helmet
        imagecopy($canvas_front, $image, $f_part, 0, $fraction * 5, $fraction, $fraction, $fraction);
        // Torso
        imagecopy($canvas_front, $image, $f_part, $f_part * 2, $f_part * 5, $f_part * 5, $f_part * 2, $f_part * 3);
        //Left Arm
        imagecopy($canvas_arm, $image, 0, 0, $f_part * 11, $f_part * 5, $f_part, $f_part * 3);
        imagecopy($canvas_front, $canvas_arm, 0, $f_part * 2, 0, 0, $f_part, $f_part * 3);
        //Right Arm
        imageflip($canvas_arm, IMG_FLIP_HORIZONTAL);
        imagecopy($canvas_front, $canvas_arm, $f_part * 3, $f_part * 2, 0, 0, $f_part, $f_part * 3);
        //Left Leg
        imagecopy($canvas_leg, $image, 0, 0, $f_part, $f_part * 5, $f_part, $f_part * 3);
        imagecopy($canvas_front, $canvas_leg, $f_part, $f_part * 5, 0, 0, $f_part, $f_part * 3);
        //Right Leg
        imageflip($canvas_leg, IMG_FLIP_HORIZONTAL);
        imagecopy($canvas_front, $canvas_leg, $f_part * 2, $f_part * 5, 0, 0, $f_part, $f_part * 3);
        //Resize
        imagecopyresized($canvas, $canvas_front, 0, 0, 0, 0,   $size, $size * 2, $fraction * 2, $fraction * 4);
        return $canvas;
    }
    public static function back($data, $size)
    {
        // Создано пока что только для скинов по шаблону 64x32
        [$image, $fraction] = $data;
        $canvas = Utils::create_canvas_transparent($size, $size * 2);
        $f_part = $fraction / 2;
        $canvas_back = Utils::create_canvas_transparent($fraction * 2, $fraction * 4);
        $canvas_arm = Utils::create_canvas_transparent($f_part, $f_part * 3);
        $canvas_leg = $canvas_arm;
        // Head
        imagecopy($canvas_back, $image, $f_part, 0, $fraction * 3, $fraction, $fraction, $fraction);
        //Helmet
        imagecopy($canvas_back, $image, $f_part, 0, $fraction * 7, $fraction, $fraction, $fraction);
        // Torso
        imagecopy($canvas_back, $image, $f_part, $f_part * 2, $f_part * 8, $f_part * 5, $f_part * 2, $f_part * 3);
        //Left Arm
        imagecopy($canvas_arm, $image, 0, 0, $f_part * 13, $f_part * 5, $f_part, $f_part * 3);
        imagecopy($canvas_back, $canvas_arm, $f_part * 3, $f_part * 2, 0, 0, $f_part, $f_part * 3);
        //Right Arm
        imageflip($canvas_arm, IMG_FLIP_HORIZONTAL);
        imagecopy($canvas_back, $canvas_arm, 0, $f_part * 2, 0, 0, $f_part, $f_part * 3);
        //Left Leg
        imagecopy($canvas_leg, $image, 0, 0, $f_part * 3, $f_part * 5, $f_part, $f_part * 3);
        imagecopy($canvas_back, $canvas_leg, $f_part * 2, $f_part * 5, 0, 0, $f_part, $f_part * 3);
        //Right Leg
        imageflip($canvas_leg, IMG_FLIP_HORIZONTAL);
        imagecopy($canvas_back, $canvas_leg, $f_part, $f_part * 5, 0, 0, $f_part, $f_part * 3);
        //Resize
        imagecopyresized($canvas, $canvas_back, 0, 0, 0, 0,   $size, $size * 2, $fraction * 2, $fraction * 4);
        return $canvas;
    }
    public static function avatar($data, $size)
    {
        [$image, $fraction] = $data;
        $canvas = Utils::create_canvas_transparent($size, $size);
        imagecopyresized($canvas, $image, 0, 0, $fraction, $fraction, $size, $size, $fraction, $fraction);
        imagecopyresized($canvas, $image, 0, 0, $fraction * 5, $fraction, $size, $size, $fraction, $fraction);
        return $canvas;
    }
    public static function cloak_resize($data, $size)
    {
        $image = imagecreatefromstring($data);
        $width = imagesx($image);
        $fraction = $width / 64;
        $canvas = Utils::create_canvas_transparent($size * 22, $size * 17);
        imagecopyresized($canvas, $image, 0, 0, 0, 0, $size * 22, $size * 17, $fraction * 22, $fraction * 17);
        return $canvas;
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
            switch ($method) {
                case 'skin':
                    $data = Constants::getSkin($login);
                    header("Content-type: image/png");
                    responseTexture($data);
                    break;
                default:
                    $filename = Constants::CACHE_FOLDER . strtolower($method) . '/' . strtolower($login) . '.png';
                    Utils::removeCacheFiles($method);
                    if (!Check::cacheValid($filename, $occurrences::$size)) {
                        Utils::saveCacheFile($login, Modifier::$method(Check::skin($login), $occurrences::$size), $method);
                    }
                    header("Content-type: image/png");
                    responseTexture(Utils::loadCacheFile($filename));
                    break;
            }
            break;
        case 'cloak':
        case 'cloak_resize':
            switch ($method) {
                case 'cloak':
                    $data = Constants::getCloak($login);
                    header("Content-type: image/png");
                    responseTexture($data);
                    break;
                default:
                    $filename = Constants::CACHE_FOLDER . strtolower($method) . '/' . strtolower($login) . '.png';
                    Utils::removeCacheFiles($method);
                    if (!Check::cacheValid($filename, $occurrences::$size * 22)) {
                        Utils::saveCacheFile($login, Modifier::$method(Constants::getCloak($login), $occurrences::$size), $method);
                    }
                    header("Content-type: image/png");
                    responseTexture(Utils::loadCacheFile($filename));
                    break;
            }
            break;
        default:
            $data = Constants::getSkin($login);
            header("Content-type: image/png");
            responseTexture($data);
    }
}
function responseTexture($data = null)
{
    if ($data) {
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
