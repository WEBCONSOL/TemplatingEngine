<?php

namespace GX2CMS\TemplateEngine\Util;

final class PregUtil
{
    private function __construct(){}

    public static function getMatches(string $pattern, $subject): array {
        $matches = array();
        preg_match_all($pattern, $subject, $matches);
        if (self::matchesFound($matches)) {
            return $matches;
        }
        return array();
    }

    public static function matchesFound(array &$matches): bool {
        if (sizeof($matches) > 1 && is_array($matches[1]) && isset($matches[1][0]) && strlen($matches[1][0])) {
            return true;
        }
        return false;
    }
}