<?php

namespace GX2CMS\TemplateEngine\Util;

class Response extends \GuzzleHttp\Psr7\Response
{
    const CODE_OK = 200;
    const CODE_FORBIDDEN = 403;
    const CODE_NOTFOUND = 404;
    const CODE_INTERNAL_SERVER_ERROR = 500;
    const CODE_INVALID_LOGIN_CREDENTIALS = 4004;
    const CODE_INVALID_OAUTH_CREDENTIALS = 5004;
    const MESSAGE_200 = "Successfully processed.";
    const MESSAGE_403 = "Forbidden. You don't have permission to access this resource.";
    const MESSAGE_404 = "Resource Not Found. We cannot find the resource you are requesting.";
    const MESSAGE_500 = "Internal Server Error.";
    private static $DEBUG = false;

    public static function setDebug(bool $isDebug) {
        self::$DEBUG = $isDebug;
    }

    public static function renderAsJSON(int $code, string $message, array $data=null)
    {
        $obj = array('success'=>$code===200, 'statusCode'=>$code, 'message'=>$message, 'data'=>$data);
        if (self::$DEBUG) {
            $obj['backtrace'] = debug_backtrace();
        }
        header("Content-Type: application/json; charset=utf-8");
        //header('Content-Disposition','attachment;filename="'.uniqid('json-file-').'.json"');
        die(json_encode($obj));
    }

    public static function renderJSONString(string $data)
    {
        header("Content-Type: application/json; charset=utf-8");
        //header('Content-Disposition','attachment;filename="'.uniqid('json-file-').'.json"');
        die($data);
    }

    public static function renderPlaintext(string $data)
    {
        header("Content-Type: text/html; charset=utf-8");
        die($data);
    }

    public static function redirect($url) {header("Location: " . $url, true, 301);}

    public static function get($name, $valuePrefix = '') {
        $nameLength = strlen($name);
        $valuePrefixLength = strlen($valuePrefix);
        $headers = headers_list();
        foreach ($headers as $header) {
            if (substr($header, 0, $nameLength) === $name) {
                if (substr($header, $nameLength + 2, $valuePrefixLength) === $valuePrefix) {
                    return $header;
                }
            }
        }
        return null;
    }

    public static function set($name, $value) {header($name.': '.$value);}

    public static function add($name, $value) {header($name.': '.$value, false);}

    public static function remove($name, $valuePrefix = '') {
        if (empty($valuePrefix)) {
            header_remove($name);
        }
        else {
            $found = self::get($name, $valuePrefix);
            if (isset($found)) {
                header_remove($name);
            }
        }
    }

    public static function take($name, $valuePrefix = '') {
        $found = self::get($name, $valuePrefix);
        if (isset($found)) {
            header_remove($name);
            return $found;
        }
        else {
            return null;
        }
    }
}