<?php

namespace Template\Util;

use Template\DefaultTemplate\ApiAttrs;
use Template\Model\Context;

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
        $tok = new Tokenizer($str);
        $statement = array();
        $list = array('vars' => array(), 'statement' => '');
        foreach ($tok->tokens as $k=>$token) {
            if ($token[0] === "." && isset($tok->tokens[$k+1])) {
                $list['vars'][sizeof($list['vars'])-1] .= $token[1] . $tok->tokens[$k+1][1];
            }
            else if ($token[0] === 319 && (!isset($tok->tokens[$k-1]) || (isset($tok->tokens[$k-1]) && $tok->tokens[$k-1][0] !== '.'))) {
                $token[1] = '$' . $token[1];
                $list['vars'][] = str_replace('$', '', $token[1]);
            }

            $statement[] = $token[1];
        }
        $list['statement'] = str_replace('.$', '.', implode('', $statement));
        return $list;
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
        return substr($str, 0, 2)===ApiAttrs::TAG_EZPZ_OPEN && $str[strlen($str)-1]===ApiAttrs::TAG_EZPZ_CLOSE;
    }
}