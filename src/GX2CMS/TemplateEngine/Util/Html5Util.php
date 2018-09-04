<?php

namespace GX2CMS\TemplateEngine\Util;

use GX2CMS\TemplateEngine\HTML5;

final class Html5Util
{
    public static $patterns = array(
        '&amp;&amp;',
        '&lt;',
        '&gt;',
        '&amp;nbsp;',
        '&amp;',
        '<'.GX2CMS_TECHNOLOGY_SLY_TAG.'>',
        '<'.GX2CMS_TECHNOLOGY_SLY_TAG.' data-ezpz-remove="true">',
        '</'.GX2CMS_TECHNOLOGY_SLY_TAG.'>',
        '<'.GX2CMS_PLATFORM_TAG.'>',
        '<'.GX2CMS_PLATFORM_TAG.' data-ezpz-remove="true">',
        '</'.GX2CMS_PLATFORM_TAG.'>'
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
            self::normalize($buffer);
            self::cleanup($buffer);
        }
        else {
            $buffer = $html5->saveHTML($dom);
            self::normalize($buffer);
            self::cleanup($buffer);
        }

        return $buffer;
    }

    private static function normalize(string &$buffer) {
        $buffer = str_replace(self::$patterns, self::$replaces, $buffer);
    }

    private static function cleanup(string &$buffer) {
        $pattern = '/<'.GX2CMS_PLATFORM_TAG.'(.[^>]*)>/';
        $matches = PregUtil::getMatches($pattern, $buffer);
        if (sizeof($matches) && !in_array(INJECT_CSS_SFX, $matches[1]) && !in_array(INJECT_JS_SFX, $matches[1])) {
            $buffer = str_replace($matches[0], '', $buffer);
        }

        $pattern = '/<'.GX2CMS_TECHNOLOGY_SLY_TAG.'(.[^>]*)>/';
        $matches = PregUtil::getMatches($pattern, $buffer);
        if (sizeof($matches)) {
            $buffer = str_replace($matches[0], '', $buffer);
        }

        $buffer = str_replace(array(
            '<'.GX2CMS_INJECT_CSS.'></'.GX2CMS_INJECT_CSS.'>', '<'.GX2CMS_INJECT_JS.'></'.GX2CMS_INJECT_JS.'>'
        ), '', $buffer);
    }
}