<?php

namespace GX2CMS\TemplateEngine\Util;

final class StringUtil
{
    private function __construct(){}

    public static function startsWith($haystack, $needle): bool {return (substr($haystack, 0, strlen($needle)) === $needle);}

    public static function endsWith($haystack, $needle): bool {return substr($haystack, - strlen(strlen($needle))) === $needle;}

    public static function hasTag(string &$buffer): bool {$noTag = strip_tags($buffer);return $noTag !== $buffer;}

    public static function contains(string &$haystack, $needle): bool {return strpos($haystack, $needle) !== false;}

    public static function formatHandlebarBuffer(string &$buffer) {
        if ($buffer === "''") {
            $buffer = "";
        }
        else if (strlen($buffer) > 2 && $buffer[0] == "'" && $buffer[strlen($buffer)-1] == "'") {
            $buffer = substr($buffer, 1, -1);
        }
    }

    public static function str2regex($str): string {return $str && is_string($str) ? "/" . preg_quote($str, "/") . "/" : "";}

    public static function isRegex($str): bool {return @preg_match("/^\\/[\\s\\S]+\\/$/", $str) ? true : false;}

    public static function cleanstr($string): string{return preg_replace("/[^A-Za-z0-9 ]/", '', $string);}

    public static function isBase64Encoded($val): bool {return base64_encode(base64_decode($val, true)) === $val;}

    public static function isValidJSON(string $str): bool {
        if ($str) {
            $str = trim($str);
            $first = $str[0];
            $last = $str[strlen($str) - 1];
            if (($first === "{" && $last === "}") || ($first === "[" && $last === "]")) {
                if (is_string($str) && is_array(json_decode($str, true)) && (json_last_error() == JSON_ERROR_NONE)) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function isAllNumericValue($val): bool {
        $val = is_array($val) ? $val : explode(",", $val);
        if (!sizeof($val)) {
            return false;
        }
        foreach ($val as $v) {
            if (!is_numeric($v)) {
                return false;
            }
        }
        return true;
    }

    public static function isEmail($str): bool {return filter_var($str, FILTER_VALIDATE_EMAIL);}

    public static function isUrl($str): bool {return filter_var($str, FILTER_VALIDATE_URL);}

    public static function isInt($str): bool {return filter_var($str, FILTER_VALIDATE_INT);}

    public static function isIP($str): bool {return filter_var($str, FILTER_VALIDATE_IP);}

    public static function isSecureHttp($uri): bool {return self::startsWith($uri, Constants::SCHEMA_HTTPS);}

    public static function isRegExp($str): bool {return @preg_match($str, '') !== false ? true : false;}

    public static function isValidMd5($md5 =''): bool{return preg_match('/^[a-f0-9]{32}$/', $md5) ? true : false;}

    public static function removeHtmlComments(string $str): string {return preg_replace('/<!--(.[^>]*?)-->/', '', $str);}

    public static function removeDoubleSlashes(string $str): string {return preg_replace('/\/+/', '/', $str);}
}