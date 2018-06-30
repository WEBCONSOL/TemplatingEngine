<?php

namespace GX2CMS\TemplateEngine\Util;

use Utilities\ListUtil;

class TernaryParser
{
    public static function parse(\GX2CMS\TemplateEngine\Model\Context $context, string $subject)
    {
        $originalSubject = $subject;
        $list = self::extractValues($subject);
        $matches = $list->getAsArray();
        if ($list->count() >= 4) {
            for($i=1; $i<$list->count(); $i++) {
                $matches[$i][0] = str_replace('itemList.', '@', $matches[$i][0]);
            }

            $tokens = array('===',      '!==',      '!=',       '==',       '>=',   '<=',   '>',    '<');
            $funcs1 = array('eq',       'noteq',    'noteq',    'eq',       'gteq', 'lteq', 'gt',   'lt');
            $f1 = '';
            foreach ($tokens as $i=>$token) {
                $parts = explode($token, $matches[1][0]);
                if (sizeof($parts) === 2) {
                    $f1 = $funcs1[$i];
                    $matches[1][0] = $parts[0] . ' ' . $parts[1];
                }
            }
            if ($f1) {
                $newSubject = '{{#'.$f1.' '.end($matches[1]).'}}'.end($matches[2]).'{{else}}'.end($matches[3]).'{{/'.$f1.'}}';
            }
            else {
                $newSubject = '{{#if '.end($matches[1]).'}}'.end($matches[2]).'{{else}}'.end($matches[3]).'{{/if}}';
            }
            $subject = str_replace($matches[0][0], $newSubject, $originalSubject);
        }
        return $subject;
    }

    public static function isTernary(string $str): bool {

        $list = self::extractValues($str);
        $tmp = preg_replace('/[\s\t\r\n]/', '', $str);
        $first2Chars = strlen($tmp) > 2 ? substr($tmp,0,2) : '';
        $lastChar = strlen($tmp) > 0 ? $tmp[strlen($tmp)-1] : '';
        if ($list->count() >= 4) {
            if (strpos($str, '?') !== false && strpos($str, ':') !== false &&
                (($first2Chars==='${' && $lastChar==='}') || (strpos($tmp, '${') !== false && strpos($tmp, '}') !== false))) {
                return true;
            }
        }
        return strpos($str, '?') !== false && strpos($str, ':') !== false &&
            (($first2Chars==='${' && $lastChar==='}') || (strpos($tmp, '${') !== false && strpos($tmp, '}') !== false));
    }

    private static function tokenize(string $subject): \Utilities\ListUtil
    {
        $matches = self::extractValues($subject)->getAsArray();
        $tokens = array();
        for ($i=1; $i < sizeof($matches); $i++) {
            $tokens[] = $matches[$i][0];
        }
        $vars = array();
        foreach ($tokens as $token) {
            if (!(is_numeric($token) || $token==='true' || $token === 'false' || $token[0] === "'" || $token[0] === '"' || !strlen($token))) {
                $vars[] = $token;
            }
        }
        return new ListUtil(array('vars'=>$vars,'statement'=>str_replace(array('${','}'),'',$subject)));
    }

    private static function extractValues(string $subject): ListUtil {

        $subject = self::cleanup($subject);
        $pattern = '/[\?:\(\)]/';
        $matches = array();
        preg_match_all($pattern, $subject, $matches);
        $matches = $matches[0];
        $tokens = array();
        for ($i=0; $i<sizeof($matches); $i++) {
            if ($matches[$i] === '?' && isset($matches[$i+1]) && $matches[$i+1] === '(') {
                $tokens[] = '\\'.$matches[$i].'\\'.$matches[$i+1];
                $i++;
            }
            else if (isset($matches[$i]) && isset($matches[$i+1]) && isset($matches[$i+2]) &&
                $matches[$i] === ')' && $matches[$i+1] === ':' && $matches[$i+2] === '('
            ) {
                $tokens[] = '\\'.$matches[$i].'\\'.$matches[$i+1].'\\'.$matches[$i+2];
                $i++;
                $i++;
            }
            else {
                $tokens[] = '\\'.$matches[$i];
            }
        }
        if (strpos($subject,'${') !== false && strpos($subject, '}') !== false) {
            $pattern = '/\${(.*)'.implode('(.*)', $tokens).'(.*)}/';
        }
        else {
            $pattern = '/(.*)'.implode('(.*)', $tokens).'(.*)/';
        }
        preg_match_all($pattern, $subject, $matches);
        return new ListUtil($matches);
    }

    private static function cleanup(string $subject): string
    {
        $pattern = '/\${(.[^}]*)\?(.[^}]*)\:(.[^}]*)}/';
        $matches = array();
        preg_match_all($pattern, $subject, $matches);
        if (sizeof($matches) > 1 && isset($matches[0][0]) && isset($matches[1][0]) && $matches[0][0] && $matches[1][0]) {
            $tokens = array('\${', '}', '\(', '\)', ':', '\?');
            foreach ($tokens as $token) {
                $subject = preg_replace('/'.$token.'\\s+/', str_replace('\\', '', $token), $subject);
                $subject = preg_replace('/\\s+'.$token.'/', str_replace('\\', '', $token), $subject);
            }
        }
        return $subject;
    }
}