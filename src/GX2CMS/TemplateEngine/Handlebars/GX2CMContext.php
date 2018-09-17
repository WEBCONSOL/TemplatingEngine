<?php

namespace GX2CMS\TemplateEngine\Handlebars;

use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\Util\StringUtil;

class GX2CMContext extends Context
{
    private $conditional_statement_replaces = array("!", "=", "&", "|", ">", "<", "'", '"');
    private $conditional_statement_patterns = array(GX2CMS_NEGATE_SIGN, GX2CMS_EQ_SIGN, GX2CMS_AND_SIGN, GX2CMS_OR_SIGN, GX2CMS_GT_SIGN, GX2CMS_LT_SIGN, GX2CMS_SINGLE_QUOTE, GX2CMS_DOUBLE_QUOTE);

    public function __construct($context = null)
    {
        parent::__construct($context);
    }

    public function get($variableName, $strict = false)
    {
        if ($this->isConditionalStatement($variableName)) {
            $variableName = str_replace($this->conditional_statement_patterns, $this->conditional_statement_replaces, $variableName);
            $token = CompilerUtil::conditionalExpressionTokenizer($variableName);
            if (isset($token['vars']) && is_array($token['vars']) && isset($token['statement'])) {
                $statement = $token['statement'];
                foreach ($token['vars'] as $var) {
                    if ($var === 'true' || $var === 'false') {
                        ${$var} = $var==='true'?true:false;
                    }
                    else if (!$this->getConstant($var)) {
                        $newVarName = str_replace('.', '_', $var);
                        $statement = str_replace($var, $newVarName, $statement);
                        ${$newVarName} = $this->get($var);
                    }
                }
                return eval('return (' . $statement . ');');
            }
        }

        if ($variableName instanceof StringWrapper) {
            $ret = (string)$variableName;
            if ($ret == "''") {
                $ret = "";
            }
            return $ret;
        }

        $constant = $this->getConstant($variableName);

        if ($constant) {
            return $constant;
        }
        else if ($variableName === "''") {
            return "";
        }
        else {
            $htmlBlock = $this->htmlBlock($variableName);
            if ($htmlBlock) {
                return $htmlBlock;
            }
            else {
                $val = parent::get($variableName, $strict);
                if (!$val) {
                    if ($variableName === 'item') {
                        $variableName = '@'.$variableName;
                    }
                    else if (StringUtil::startsWith($variableName, 'itemList.')) {
                        $parts = explode('.', strtolower($variableName));
                        $parts[0] = '@'.$parts[0];
                        $variableName = implode('', $parts);
                    }
                    else if (StringUtil::startsWith($variableName, 'item.')) {
                        $variableName = str_replace('item.', 'this.', $variableName);
                    }
                    $val = parent::get($variableName, $strict);
                    if ($val) {
                        return $val;
                    }
                }
                else {
                    return $val;
                }
            }
        }

        return parent::get($variableName, $strict);
    }

    private function getConstant($varName): string {
        if (is_numeric($varName)) {
            return $varName;
        }
        if (is_bool($varName) || $varName === 'true' || $varName === 'false') {
            return $varName === 'false' || !$varName ? false : true;
        }
        $first = $varName[0];
        $last = $varName[strlen($varName)-1];
        if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
            if ($first === "'" && $last === "'") {
                $varName = substr($varName, 1, -1);
            }
            return $varName;
        }
        if ($first === "[" && $last === "]") {
            $arr = json_decode($varName, true);
            if ($arr) {
                return implode(',', $arr);
            }
            else {
                Response::renderPlaintext("Malformated literal: " . str_replace(array("'", '"'), '', $varName));
            }
        }
        return "";
    }

    private function htmlBlock($varName): string {
        if (StringUtil::hasTag($varName)) {
            return $varName;
        }
        return "";
    }

    private function isConditionalStatement($variableName) {
        foreach ($this->conditional_statement_replaces as $char) {
            if (strpos($variableName, $char) !== false) {
                return true;
            }
        }
        foreach ($this->conditional_statement_patterns as $char) {
            if (strpos($variableName, $char) !== false) {
                return true;
            }
        }
    }
}