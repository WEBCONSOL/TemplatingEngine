<?php

namespace GX2CMS\TemplateEngine\Util;

final class StringUtil
{
    private function __construct(){}

    public static function startsWith($haystack, $needle): bool {return (substr($haystack, 0, strlen($needle)) === $needle);}

    public static function endsWith($haystack, $needle): bool {return substr($haystack, - strlen(strlen($needle))) === $needle;}

    public static function hasTag(string &$buffer): bool {
        $noTag = strip_tags($buffer);
        return $noTag !== $buffer;
    }
}