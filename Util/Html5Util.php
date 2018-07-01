<?php

namespace GX2CMS\TemplateEngine\Util;

use Masterminds\HTML5;

final class Html5Util
{
    public static $patterns = array(
        '&amp;&amp;',
        '&lt;',
        '&gt;',
        '&amp;nbsp;',
        '&amp;',
        '<sly>',
        '<sly data-ezpz-remove="true">',
        '</sly>',
        '<ezpz>',
        '<ezpz data-ezpz-remove="true">',
        '</ezpz>'
    );

    public static $replaces = array(
        '&&',
        '<',
        '>',
        '&nbsp;',
        '&',
        '',
        '',
        '',
        '',
        '',
        '',
    );

    private function __construct(){}

    public static function formatOutput(HTML5 &$html5, &$dom, bool $removeDoc=true): string {
        if ($removeDoc) {
            $parts = explode('<html', $html5->saveHTML($dom));
            $parts = explode('</html>', $parts[sizeof($parts) - 1]);
            $buffer = substr($parts[0], 1);
            $buffer = self::normalize($buffer);
            self::cleanup($buffer);
        }
        else {
            $buffer = self::normalize($html5->saveHTML($dom));
            self::cleanup($buffer);
        }

        return $buffer;
    }

    public static function normalize($buffer): string {
        if (StringUtil::startsWith($buffer, "'<!DOCTYPE html>")) {
            $buffer = substr($buffer, 1);
        }
        return str_replace(self::$patterns, self::$replaces, $buffer);
    }

    private static function cleanup(string &$buffer) {
        $pattern = '/<ezpz(.[^>]*)>/';
        $matches = PregUtil::getMatches($pattern, $buffer);
        if (sizeof($matches)) {
            $buffer = str_replace($matches[0], '', $buffer);
        }

        $pattern = '/<sly(.[^>]*)>/';
        $matches = PregUtil::getMatches($pattern, $buffer);
        if (sizeof($matches)) {
            $buffer = str_replace($matches[0], '', $buffer);
        }
    }
}