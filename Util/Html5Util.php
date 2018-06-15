<?php

namespace Template\Util;

use Masterminds\HTML5;

final class Html5Util
{
    public static $patterns = array(
        '&amp;&amp;',
        '&lt;',
        '&gt;',
        '<sly>',
        '</sly>'
    );
    public static $replaces = array(
        '&&',
        '<',
        '>',
        '',
        ''
    );

    private function __construct(){}

    public static function formatOutput(HTML5 &$html5, &$dom, bool $removeDoc=true): string {
        if ($removeDoc) {
            $parts = explode('<html', $html5->saveHTML($dom));
            $parts = explode('</html>', $parts[sizeof($parts) - 1]);
            $buffer = substr($parts[0], 1);
            return self::normalize($buffer);
        }
        else {
            return self::normalize($html5->saveHTML($dom));
        }
    }

    public static function normalize($buffer): string {
        return str_replace(self::$patterns, self::$replaces, $buffer);
    }
}