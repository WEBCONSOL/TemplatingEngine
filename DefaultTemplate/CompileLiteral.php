<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\PregUtil;
use GX2CMS\TemplateEngine\Util\StringUtil;
use GX2CMS\TemplateEngine\Util\TernaryParser;

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
        if (TernaryParser::isTernary($data))
        {
            return TernaryParser::parse($context, $data);
        }
        else if (preg_match('/(\${item})/', $data))
        {
            $data = preg_replace('/\${item}/', '{{this}}', $data);
        }
        else if (CompilerUtil::isLiteral($data) && strlen($data) && !StringUtil::hasTag($data))
        {
            $newData = '';
            $matches = CompilerUtil::parseLiteral($data);

            if (sizeof($matches)) {

                foreach ($matches[0] as $i=>$v) {
                    $callback = self::getCallback($v);
                    if ($callback) {
                        $matches[1][$i] = str_replace('{item}', '{this}', $callback($context, $v));
                    }
                }

                $newData = str_replace($matches[0], $matches[1], $data);
            }
            else {
                $callback = self::getCallback($data);
                if ($callback) {
                    $newData = str_replace('{item}', '{this}', $callback($context, $data));
                }
            }

            $data = $newData;
        }

        return trim($data);
    }

    /**
     * @param \DOMText          $node
     * @param Context           $context
     * @param Tmpl              $tmpl
     * @param InterfaceEzpzTmpl $engine
     */
    public static function process(\DOMText &$node, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine)
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

        $l = PregUtil::getMatches('/\${(.[^}]*)@([^}]*)}/', $data);
        if (sizeof($l) === 3) {
            $cnt = PregUtil::getMatches("/context='(.[^']*)'/", $l[2][0]);
            if (!empty($cnt)) {
                return ApiAttrs::TAG_HB_OPEN . '{' . trim($l[1][0]) . '}' . ApiAttrs::TAG_HB_CLOSE;
            }
            else {
                $cnt = PregUtil::getMatches("/i18n(.[^']*)locale='(.[^']*)'/", $l[2][0]);
                if (sizeof($cnt) === 3) {
                    $keys = array(
                        'i18n',
                        preg_replace('/[\s\n\r\'"]/', '', $cnt[2][0]),
                        preg_replace('/[\s\n\r\'"]/', '', $l[1][0])
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
        $var = CompilerUtil::removeOpenCloseEzpzTag($data);
        if ($context->has($var)) {
            return $context->get($var);
        }
        $val = CompilerUtil::getVarValue($context, explode('.', $var));
        if ($val) {
            return is_array($val) || is_object($val) ? json_encode($val) : $val;
        }
        return self::handleConstant($data);
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private static function handleConstant(string $data): string {
        $var = CompilerUtil::removeOpenCloseEzpzTag($data);
        $first = $var[0];
        $last = $var[strlen($var)-1];

        if (($first === "'" && $last === "'") || ($first === '"' && $last === '"'))
        {
            return substr($var, 1, -1);
        }
        else if (is_bool($var) || is_numeric($var) || $var === 'true' || $var === 'false')
        {
            return $var;
        }
        else if ($first === "[" && $last === ']')
        {
            $var = json_decode($var, true);
            return is_array($var) ? implode(',', $var) : '';
        }
        return CompilerUtil::openCloseHBTag($data);
    }
}