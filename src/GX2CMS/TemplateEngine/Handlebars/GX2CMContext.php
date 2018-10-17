<?php

namespace GX2CMS\TemplateEngine\Handlebars;

use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\Constants;
use GX2CMS\TemplateEngine\Util\RegexConstants;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\Util\StringUtil;
use WC\Utilities\PregUtil;

class GX2CMContext extends Context
{
    private $customContext = array();

    public function __construct($context = null)
    {
        $this->customContext = $context;
        parent::__construct($context);
    }

    public function get($variableName, $strict = false)
    {
        $val = $this->handleContext($variableName);
        if ($val === null) {
            // variable is found
            $val = parent::get($variableName, $strict);
            if ($val) {return $val;}
            // empty
            if ($variableName === "''") {return "";}
            // logical statement (i.e. if else or unary statement)
            else if ($this->isConditionalStatement($variableName)) {
                $variableName = str_replace(Constants::REPLACES, Constants::PATTERNS, $variableName);
                $token = CompilerUtil::conditionalExpressionTokenizer($variableName);
                if (isset($token['vars']) && is_array($token['vars']) && isset($token['statement'])) {
                    if (sizeof($token['vars'])===2 && StringUtil::contains($variableName, '||')) {
                        $token['vars'][0] = !$this->getConstant($token['vars'][0]) ? parent::get($token['vars'][0]) : $this->getConstant($token['vars'][0]);
                        $token['vars'][1] = !$this->getConstant($token['vars'][1]) ? parent::get($token['vars'][1]) : $this->getConstant($token['vars'][1]);
                        return $token['vars'][0] ? $token['vars'][0] : $token['vars'][1];
                    }
                    else {
                        $statement = $token['statement'];
                        foreach ($token['vars'] as $var) {
                            if ($var === 'true' || $var === 'false') {
                                ${$var} = $var==='true'?true:false;
                            }
                            else if (!$this->getConstant($var)) {
                                $newVarName = str_replace(array('.','-'), '_', $var);
                                $statement = str_replace($var, $newVarName, $statement);
                                $val = parent::get(str_replace('item.', 'this.', $var));
                                ${$newVarName} = $val==='true'?true:($val==='false'?false:$val);
                            }
                        }
                        return eval('return (' . $statement . ');');
                    }
                }
            }
            // variable within the loop context
            else if ($variableName === 'item') {
                $variableName = '@'.$variableName;
                $val = parent::get($variableName, $strict);
                if ($val) {return $val;}
            }
            if (StringUtil::startsWith($variableName, 'itemList.')) {
                $parts = explode('.', strtolower($variableName));
                $parts[0] = '@'.$parts[0];
                $variableName = implode('', $parts);
                $val = parent::get($variableName, $strict);
                if ($val) {return $val;}
            }
            else if (StringUtil::startsWith($variableName, 'item.')) {
                $variableName = str_replace('item.', 'this.', $variableName);
                $val = parent::get($variableName, $strict);
                if ($val) {return $val;}
            }
            else if ($variableName instanceof StringWrapper) {
                $ret = (string)$variableName;
                if ($ret == "''") {$ret = "";}
                return $ret;
            }
            // constant
            else if ($this->getConstant($variableName)) {return $this->getConstant($variableName);}
            else {
                $htmlBlock = $this->htmlBlock($variableName);
                if ($htmlBlock) {return $htmlBlock;}
                else {
                    $vars = $this->_splitVariableName($variableName);
                    $val = $this->_findVariableInContext($vars, $this->customContext);
                    if ($val !== null) {return $val;}
                    return parent::get($variableName, $strict);
                }
            }
        }
        else {
            return is_array($val)||is_object($val)?json_encode($val):$val;
        }
    }

    private function handleContext($data) {
        if (StringUtil::contains($data, '@')&&StringUtil::contains($data, 'context')) {
            $data = str_replace(array('"',' @ ','&quot;'), array("'","@","'"), $data);
        }
        $matches = PregUtil::getMatches(RegexConstants::CONTEXT, $data);
        if (sizeof($matches) >= 4) {
            $varName = trim($matches[1][0]);
            $contextName = $matches[3][0];
            if (in_array($contextName, RegexConstants::$allowedContext)) {
                $val = self::get($varName);
                if ($val) {
                    return is_array($val)||is_object($val)?json_encode($val):$val;
                }
            }
            else {
                Response::renderPlaintext("You are using forbidden context: ".$contextName.". Allowed context are: " . implode(', ', RegexConstants::$allowedContext));
            }
        }
        return StringUtil::contains($data, '@')&&StringUtil::contains($data, 'context')?"":null;
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
        foreach (Constants::REPLACES as $char) {
            if (strpos($variableName, $char) !== false) {
                return true;
            }
        }
        foreach (Constants::PATTERNS as $char) {
            if (strpos($variableName, $char) !== false) {
                return true;
            }
        }
    }

    private function _splitVariableName($variableName): array
    {
        $bad_chars = preg_quote(self::NOT_VALID_NAME_CHARS, '/');
        $bad_seg_chars = preg_quote(self::NOT_VALID_SEGMENT_NAME_CHARS, '/');

        $name_pattern = "(?:[^"
            . $bad_chars
            . "\s]+)|(?:\[[^"
            . $bad_seg_chars
            . "]+\])";

        $check_pattern = "/^(("
            . $name_pattern
            . ")\.)*("
            . $name_pattern
            . ")\.?$/";

        $get_pattern = "/(?:" . $name_pattern . ")/";

        if (!preg_match($check_pattern, $variableName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Variable name is invalid: "%s"',
                    $variableName
                )
            );
        }

        preg_match_all($get_pattern, $variableName, $matches);

        $chunks = array();
        foreach ($matches[0] as $chunk) {
            // Remove wrapper braces if needed
            if ($chunk[0] == '[') {
                $chunk = substr($chunk, 1, -1);
            }
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    private function _findVariableInContext($variable, array $context)
    {
        $value = null;
        if (is_array($variable)) {
            foreach ($variable as $var) {
                if (isset($context[$var])) {
                    $value = $context[$var];
                    $context = $value;
                }
                else {
                    $value = null;
                }
            }
        }
        else if (is_string($variable) && isset($context[$variable])) {
            $value = $context[$variable];
        }
        return $value;
    }
}