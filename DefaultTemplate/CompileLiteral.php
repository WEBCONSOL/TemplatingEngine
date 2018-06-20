<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\Lib\Util;
use GX2CMS\TemplateEngine\EzpzTmplInterface;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\CompilerUtil;

class CompileLiteral
{
    /**
     * @param Context $context
     * @param string  $data
     *
     * @return string
     */
    public static function getParsedData(Context $context, string $data): string
    {
        if (preg_match('/(\${{this}})/', $data))
        {
            $parts = explode("\n", $data);
            foreach ($parts as $i=>$item) {
                $l = array();
                preg_match('/\${{this}}/', $item, $l);
                if (!empty($l)) {
                    $parts[$i] = str_replace($l[0], '{{{this}}}', $item);
                }
                else {
                    $callback = self::getCallback($item);
                    if (strlen($callback)) {
                        $parts[$i] = $callback($context, $item);
                    }
                }
            }
            $data = implode("\n", $parts);
        }
        else if (strlen($data))
        {
            $arr = CompilerUtil::parseLiteral($data);
            if (!empty($arr)) {
                foreach ($arr[0] as $i=>$str) {
                    $callback = self::getCallback($str);
                    if (strlen($callback)) {
                        $arr[1][$i] = $callback($context, $str);
                    }
                    else {
                        $arr[1][$i] = $str;
                    }
                }
                $data = str_replace($arr[0], $arr[1], $data);
            }
            else {
                $l = preg_split('/\${/', $data);
                $data = implode("\n".'${', $l);
                $callback = self::getCallback($data);
                if (strlen($callback)) {
                    $data = $callback($context, $data);
                }
            }
        }

        return trim($data, "\n\r\t\s");
    }

    /**
     * @param Context  $context
     * @param \DOMText $node
     */
    public static function process(\DOMText &$node, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine)
    {
        if (isset($node->data))
        {
            $node->data = self::getParsedData($context, trim($node->data));
        }
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private static function getCallback(string $data): string {
        $matches = CompilerUtil::parseLiteral($data);
        if (!empty($matches)) {
            // multiple
            if (sizeof($matches[0]) > 1) {
                return self::class . '::handleMultiple';
            }
            // @
            else if (sizeof(explode('@', $data)) == 2) {
                return self::class . '::handleContext';
            }
            // constant or variable
            else {
                return self::class . '::handleVariable';
            }
        }
        return "";
    }

    /**
     * @param Context $context
     * @param string  $data
     *
     * @return string
     */
    private static function handleMultiple(Context &$context, string $data): string {
        $matches = CompilerUtil::parseLiteral($data);
        foreach ($matches[1] as $i=>$match) {
            if ($match) {
                $callback = self::getCallback('${'.$match.'}');
                if (strlen($callback)) {
                    $matches[1][$i] = $callback($context, '${'.$match.'}');
                }
                else {
                    $matches[1][$i] = self::handleVariable($context, $match);
                }
            }
        }
        return str_replace($matches[0], $matches[1], $data);
    }

    /**
     * @param Context $context
     * @param string  $data
     *
     * @return string
     */
    private static function handleContext(Context &$context, string $data): string {

        $l = array();
        preg_match('/\${(.[^}]*)@([^}]*)}/', $data, $l);
        if (sizeof($l) === 3) {
            $cnt = array();
            preg_match('/context=\'(.*)\'/', $l[2], $cnt);
            if (!empty($cnt)) {
                return ApiAttrs::TAG_HB_OPEN . '{' . trim($l[1]) . '}' . ApiAttrs::TAG_HB_CLOSE;
            }
            else {
                $cnt = array();
                preg_match('/i18n(.*)locale=(.*)/', $l[2], $cnt);
                if (!empty($cnt) && sizeof($cnt) === 3) {
                    $keys = array(
                        'i18n',
                        preg_replace('/[\s\n\r\'"]/', '', $cnt[2]),
                        preg_replace('/[\s\n\r\'"]/', '', $l[1])
                    );
                    $val = CompilerUtil::getVarValue($context, $keys);
                    if (!empty($val)) {
                        return $val;
                    }
                    return preg_replace('/[\s\n\r\'"]/', '', $l[1]);
                }
            }
        }

        return $data;
    }

    /**
     * @param Context $context
     * @param string  $data
     *
     * @return string
     */
    private static function handleVariable(Context &$context, string $data): string {
        return $context->has($data) ? $context->get($data) : self::handleConstant($data);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private static function handleConstant(string $data): string {

        $first = $data[0];
        $last = $data[strlen($data)-1];

        if (($first === "'" && $last === "'") || ($first === '"' && $last === '"'))
        {
            return substr($data, 1, -1);
        }
        else if (is_bool($data) || is_numeric($data) || $data === 'true' || $data === 'false')
        {
            return $data;
        }
        else if ($first === "[" && $last === ']')
        {
            $data = json_decode($data, true);
            return is_array($data) ? implode(',', $data) : '';
        }
        return $data;
    }
}