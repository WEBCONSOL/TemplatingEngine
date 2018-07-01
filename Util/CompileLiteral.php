<?php

namespace GX2CMS\TemplateEngine\Util;

use GX2CMS\Lib\Response;
use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;
use GX2CMS\TemplateEngine\Model\Context;

class CompileLiteral
{
    private static $allowedContext = array('html', 'text', 'elementName', 'attributeName', 'attribute', 'uri', 'styleString', 'scriptString', 'number', 'unsafe');

    /**
     * @param Context $context
     * @param string  $data
     *
     * @return string
     */
    public static function getParsedData(Context $context, string $data): string
    {
        $matches = CompilerUtil::parseLiteral($data);
        if (!empty($matches)) {
            //print_r($matches);echo "\n";
            foreach ($matches[0] as $i=>$match) {
                $callback = self::getCallback($match);
                if ($callback) {
                    //echo $match ." -> ".$callback,"\n";
                    $matches[1][$i] = $callback($context, $matches[1][$i]);
                }
            }
            //print_r($matches);echo "\n";
            $data = str_replace($matches[0], $matches[1], $data);
        }

        return $data;
    }

    /**
     * @param \DOMText $node
     * @param Context  $context
     */
    public static function process(\DOMText &$node, Context &$context)
    {
        $node->data = self::getParsedData($context, trim($node->data));
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private static function getCallback(string $data): string {

        $pattern = RegexConstants::LITERAL;
        $matches = PregUtil::getMatches($pattern, $data);
        if (!empty($matches))
        {
            $match = $matches[1][0];
            if (sizeof(PregUtil::getMatches(RegexConstants::CONTEXT, $match))) {
                return self::class . '::handleContext';
            }
            else if (sizeof(PregUtil::getMatches(RegexConstants::I18N, $match))) {
                return self::class . '::handleI18N';
            }
            else if (TernaryParser::isTernary($data)) {
                return self::class . '::handleTernary';
            }
            else if (is_numeric($match) || is_bool($match) || $match === 'true' || $match === 'false' ||
                ($match[0]==="'" && $match[strlen($match)-1]==="'") || ($match[0]==='"' && $match[strlen($match)-1]==='"') ||
                ($match[0]==='[' && $match[strlen($match)-1]===']')
            ) {
                return self::class . '::handleConstant';
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
    private static function handleTernary(Context &$context, string $data): string {return TernaryParser::parse($context, $data);}

    /**
     * @param Context $context
     * @param string  $data
     *
     * @return string
     */
    private static function handleContext(Context &$context, string $data): string {
        $matches = PregUtil::getMatches(RegexConstants::CONTEXT, $data);
        if (sizeof($matches) >= 4) {
            $varName = trim($matches[1][0]);
            $contextName = $matches[3][0];
            if (in_array($contextName, self::$allowedContext)) {
                if ($context->has($varName)) {
                    $tmpVal = $context->get($varName);
                    if (StringUtil::hasTag($tmpVal)) {
                        return $tmpVal;
                    }
                    else {
                        return ApiAttrs::TAG_HB_CTX_OPEN . "'" . trim($context->get($varName)) . "'" . ApiAttrs::TAG_HB_CTX_CLOSE;
                    }
                }
                else {
                    $tmpVal = CompilerUtil::getVarValue($context, explode('.', $varName));
                    if ($tmpVal) {
                        if (is_array($tmpVal) || is_object($tmpVal)) {
                            $tmpVal = json_encode($tmpVal);
                        }
                        return ApiAttrs::TAG_HB_CTX_OPEN . "'" . trim($tmpVal) . "'" . ApiAttrs::TAG_HB_CTX_CLOSE;
                    }
                }
            }
            else {
                Response::renderPlaintext("You are using forbidden context: ".$contextName.". Allowed context are: " . implode(', ', self::$allowedContext));
            }
        }
        return "";
    }

    private static function handleI18N(Context &$context, string $data): string {
        $matches = PregUtil::getMatches(RegexConstants::I18N, $data);
        if (sizeof($matches) >= 4) {
            $contextName = $matches[4][0];
            $varName = $matches[1][0];
            if ($context->has('i18n')) {
                $i18n = $context->get('i18n');
                if (is_array($i18n) && isset($i18n[$contextName]) && isset($i18n[$contextName][$varName])) {
                    $tmpVal = $i18n[$contextName][$varName];
                    if ($tmpVal) {
                        if (is_array($tmpVal) || is_object($tmpVal)) {
                            $tmpVal = json_encode($tmpVal);
                        }
                        return ApiAttrs::TAG_HB_CTX_OPEN . "'" . trim($tmpVal) . "'" . ApiAttrs::TAG_HB_CTX_CLOSE;
                    }
                }
            }
            $data = str_replace(array('"',"'"), '', $varName);
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
        return ApiAttrs::TAG_HB_OPEN . trim($data) . ApiAttrs::TAG_HB_CLOSE;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private static function handleConstant(string $data): string {
        return ApiAttrs::TAG_HB_OPEN . "'" . trim($data) . "'" . ApiAttrs::TAG_HB_CLOSE;
    }
}