<?php

namespace GX2CMS\TemplateEngine\Util;

use Utilities\ListUtil;

class TernaryParser
{
    public static function parse(\GX2CMS\TemplateEngine\Model\Context $context, string $subject)
    {
        $matches = PregUtil::getMatches(RegexConstants::TERNARY_VAL, $subject);
        if (empty($matches)) {
            $matches = PregUtil::getMatches(RegexConstants::TERNARY_VAR, $subject);
        }

        if (!empty($matches)) {
            $list = new ListUtil($matches);
            $matches = $list->getAsArray();
            if ($list->count() >= 4 && sizeof($list->get(0)) && sizeof($list->get(1)) && sizeof($list->get(1)) && sizeof($list->get(3))) {
                $tokens = array('===',      '!==',      '!=',       '==',       '>=',   '<=',   '>',    '<');
                $funcs = array('eq',       'noteq',    'noteq',    'eq',       'gteq', 'lteq', 'gt',   'lt');
                $f1 = '';
                foreach ($tokens as $i=>$token) {
                    $parts = explode($token, $matches[1][0]);
                    if (sizeof($parts) === 2) {
                        $f1 = $funcs[$i];
                        $matches[1][0] = $parts[0] . ' ' . $parts[1];
                    }
                }
                if ($f1) {
                    $newSubject = '{{#'.$f1.' '.trim(end($matches[1])).'}}'.trim(end($matches[2])).'{{else}}'.trim(end($matches[3])).'{{/'.$f1.'}}';
                }
                else {
                    $newSubject = '{{#if '.trim(end($matches[1])).'}}'.trim(end($matches[2])).'{{else}}'.trim(end($matches[3])).'{{/if}}';
                }

                $subject = str_replace($matches[0][0], $newSubject, $subject);
            }
        }
        return $subject;
    }

    public static function isTernary(string $str): bool {

        $matches = PregUtil::getMatches(RegexConstants::TERNARY_VAR, $str);
        if (empty($matches)) {
            $matches = PregUtil::getMatches(RegexConstants::TERNARY_VAL, $str);
        }
        return !empty($matches);
    }
}