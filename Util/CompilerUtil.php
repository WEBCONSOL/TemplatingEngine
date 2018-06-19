<?php

namespace GX2CMS\TemplateEngine\Util;

use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;
use GX2CMS\TemplateEngine\Model\Context;

final class CompilerUtil
{
    private function __construct(){}

    public static function literal(string $str): string {
        $parts = explode('.', $str);
        if (sizeof($parts) > 1) {
            $list = array();
            $list[] = $parts[0];
            for ($i = 1; $i < sizeof($parts); $i++) {
                $list[] = '["'.$parts[$i].'"]';
            }
            return implode('', $list);
        }
        return '$'.$str;
    }

    public static function conditionalExpressionTokenizer(string $str): array {
        $str =  preg_replace('/[\s+]/', '', $str);
        $list = preg_split('/([\||&|=|!|\(|\)])/', $str);
        foreach ($list as $i=>$v) {
            if (!$v) {
                unset($list[$i]);
            }
        }
        $vars = array();
        $list = array_unique(array_values($list));
        foreach ($list as $var) {
            $vars[] = '$'.$var;
        }
        $output = array('vars' => $list, 'statement' => str_replace($list, $vars, $str));
        return $output;
    }

    public static function getVarValue(Context &$context, array $vars) {
        $val = null;
        if ($context->hasElement() && !empty($vars)) {
            $n1 = sizeof($vars);
            $n2 = 0;
            foreach ($vars as $var) {
                if ($context->has($var)) {
                    $val = $context->get($var);
                    $n2++;
                }
                else if (is_array($val) && isset($val[$var])) {
                    $val = $val[$var];
                    $n2++;
                }
            }
            if ($n1 > $n2) {
                return null;
            }
            else {
            }
        }
        return $val;
    }

    public static function openCloseHBTag(string $str): string {
        return str_replace(
            array(ApiAttrs::TAG_EZPZ_OPEN, ApiAttrs::TAG_EZPZ_CLOSE),
            array(ApiAttrs::TAG_HB_OPEN, ApiAttrs::TAG_HB_CLOSE),
            $str
        );
    }

    public static function isLiteral(string $str): bool {
        if (substr($str, 0, 2)===ApiAttrs::TAG_EZPZ_OPEN && $str[strlen($str)-1]===ApiAttrs::TAG_EZPZ_CLOSE) {
            return true;
        }
        else {
            $pattern = '/\${(.[^}]*)}/';
            $matches = array();
            preg_match_all($pattern, $str, $matches);
            if (!empty($matches)) {
                $splits = preg_split($pattern, $str);
                if (sizeof($splits) > 1) {
                    $reverseMatches = array_reverse($matches[0]);
                    $newSplits = array();
                    foreach ($splits as $i=>$split) {
                        $newSplits[] = $split;
                        if (!empty($reverseMatches)) {
                            $newSplits[] = array_pop($reverseMatches);
                        }
                    }
                    if (implode('', $newSplits) === $str) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function parseLiteral(string &$str): array {
        $matches = array();
        preg_match_all('/\${(.[^}]*)}/', trim($str), $matches);
        if (is_array($matches) && sizeof($matches) > 1 && is_array($matches[1]) && isset($matches[1][0]) && strlen($matches[1][0])) {
            return $matches;
        }
        return array();
    }

    public static function parseLiteralWithContext(string $str): array {
        $matches = self::parseLiteral($str);
        $list = array();
        if (sizeof($matches)) {
            preg_match('/([^\}|@]*)@([^\}|=]*)context=\'(.[^\}|\)]*)\'/', $matches[1][0], $list);
            $list = array_filter($list, function($v){return !empty(trim($v));});
        }
        return $list;
    }
}