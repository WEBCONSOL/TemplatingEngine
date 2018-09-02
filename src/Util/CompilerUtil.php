<?php

namespace GX2CMS\TemplateEngine\Util;

use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;
use GX2CMS\TemplateEngine\Model\Context;

final class CompilerUtil
{
    private function __construct(){}

    // TODO: use in test api - optimize this.
    public static function conditionalExpressionTokenizer(string $str): array {
        $str =  preg_replace(RegexConstants::WHITESPACE, '', $str);
        $list = preg_split('/([\||&|=|!|\(|\)])/', $str);
        foreach ($list as $i=>$v) {
            if (!$v) {
                unset($list[$i]);
            }
        }
        $vars = array();
        $list = array_unique(array_values($list));
        foreach ($list as $var) {
            $vars[] = is_numeric($var) || strpos($var, "'") !== false || strpos($var, '"') !== false ? $var : '$'.$var;
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

        $str = trim($str);
        if (substr($str, 0, 2)===ApiAttrs::TAG_EZPZ_OPEN && $str[strlen($str)-1]===ApiAttrs::TAG_EZPZ_CLOSE) {
            return true;
        }
        else {
            $pattern = RegexConstants::LITERAL;
            $matches = PregUtil::getMatches($pattern, $str);
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

    public static function parseLiteral(string $data): array {
        return PregUtil::getMatches(RegexConstants::LITERAL, $data);
    }
}