<?php

namespace GX2CMS\TemplateEngine\Handlebars;

use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\Constants;
use GX2CMS\TemplateEngine\Util\RegexConstants;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\Util\StringUtil;
use WC\Utilities\ArrayUtil;
use WC\Utilities\PregUtil;

class GX2CMContext extends Context
{
    private $localContext = array();

    public function __construct($context = null)
    {
        $this->localContext = $context;
        parent::__construct($context);
    }

    public function get($variableName, $strict = false)
    {
        $val = $this->handleContext($variableName);
        if ($val === null) {
            // logical statement (i.e. if else or unary statement)
            if ($this->isConditionalStatement($variableName)) {
                $variableName = str_replace(Constants::REPLACES, Constants::PATTERNS, $variableName);
                if (strpos($variableName, 'itemList.') !== false) {
                    $variableName = str_replace('itemList.', '@', $variableName);
                    $this->adjustVar($variableName);
                }
                else if (strpos($variableName, 'item.') !== false) {
                    $variableName = str_replace('item.', 'this.', $variableName);
                    $this->adjustVar($variableName);
                }
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

            // empty
            if ($variableName === "''") {return "";}

            // constant
            $cstnt = $this->getConstant($variableName);
            if ($cstnt) {return $cstnt;}

            // variable within the loop context
            if ($variableName === 'item') {
                $variableName = '@'.$variableName;
                $val = parent::get($variableName, $strict);
                if ($val) {return $val;}
            }
            else if (StringUtil::startsWith($variableName, 'itemList.')) {
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
            else {

                $htmlBlock = $this->htmlBlock($variableName);
                if ($htmlBlock) {return $htmlBlock;}

                $val = ArrayUtil::search($variableName, $this->localContext);
                if ($val !== null) {
                    return $val;
                }

                // variable is found
                $val = parent::get($variableName, $strict);
                if ($val) {return $val;}

                if ($variableName instanceof StringWrapper) {
                    $ret = (string)$variableName;
                    if ($ret == "''") {$ret = "";}
                    return $ret;
                }
            }

            return parent::get($variableName, $strict);
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
        $newVariableName = str_replace(Constants::REPLACES, Constants::PATTERNS, $variableName);
        if ($variableName !== $newVariableName) {
            return true;
        }
        foreach (Constants::REPLACES as $char) {
            if (strpos($variableName, $char) !== false) {
                return true;
            }
        }
        foreach (Constants::PATTERNS as $char) {
            if (strpos($variableName, $char) !== false && !$this->isQuotedString($variableName)) {
                return true;
            }
        }
    }

    private function isQuotedString($var): bool {
        $var = trim($var);
        $len = strlen($var);
        return $len > 2 && (($var[0] === '"' && $var[$len-1] === '"') || ($var[0] === "'" && $var[$len-1] === "'"));
    }

    private function adjustVar(&$variableName) {
        $variableName = str_replace('item.', 'this.', $variableName);
        foreach (Constants::PATTERNS as $char) {
            $exp = explode($char, $variableName);
            if (sizeof($exp) > 1) {
                $uid = uniqid();
                $newVar = 'newVar'.$uid;
                $$newVar = parent::get($exp[0]);
                $exp[0] = 'newVar'.$uid;
                $variableName = implode($char, $exp);
            }
        }
    }
}