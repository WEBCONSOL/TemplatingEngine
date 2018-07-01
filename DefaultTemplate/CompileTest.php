<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\CompilerUtil;

class CompileTest implements CompileInterface
{
    /**
     * @param \DOMElement       $node
     * @param \DOMElement       $child
     * @param Context           $context
     * @param Tmpl              $tmpl
     * @param InterfaceEzpzTmpl $engine
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine): bool
    {
        $matches = CompilerUtil::parseLiteral($child->getAttribute(ApiAttrs::TEST));

        if (sizeof($matches)) {

            $token = new Context(CompilerUtil::conditionalExpressionTokenizer($matches[1][0]));
            $statement = $token->has('statement') ? $token->get('statement') : null;

            if ($token->has('vars') && !empty($statement)) {

                $vars = $token->get('vars');

                if (sizeof($vars) === 1) {

                    $var = $vars[0];

                    if ($context->has($var)) {

                        ${$var} = $context->get($var);
                    }
                    else {

                        $parts = explode('.', $var);

                        if (sizeof($parts) > 1) {

                            $val = CompilerUtil::getVarValue($context, $parts);
                            $newVarName = preg_replace('/[^A-Za-z0-9]/', '_', $var);
                            $statement = str_replace($var, $newVarName, $statement);
                            if ($val) {
                                ${$newVarName} = true;
                            }
                            else {
                                ${$newVarName} = false;
                            }
                        }
                    }
                }
                else
                {
                    foreach ($vars as $var) {

                        if ($context->has($var)) {
                            ${$var} = $context->get($var);
                        }
                        else {
                            $parts = explode('.', $var);
                            ${$var} = CompilerUtil::getVarValue($context, $parts);
                            if (!empty(${$var})) {
                                $newVarName = str_replace('.', '_', $var);
                                $statement = str_replace($var, $newVarName, $statement);
                                ${$newVarName} = ${$var};
                            }
                            else if (sizeof($parts) > 1) {
                                $newVarName = str_replace('.', '_', $var);
                                $statement = str_replace($var, $newVarName, $statement);
                                ${$newVarName} = false;
                            }
                        }
                    }
                }

                $child->removeAttribute(ApiAttrs::TEST);
                $eval = eval('return (' . $statement . ');');

                if (!$eval) {
                    $child->setAttribute(ApiAttrs::REMOVE, 'true');
                    $child->nodeValue = '';
                }
                else {
                    $newNode1 = new \DOMText();
                    $newNode1->data = '';
                    $node->insertBefore($newNode1, $child);
                    $newNode2 = new \DOMText();
                    $newNode2->data = '';
                    $node->insertBefore($newNode2, $child->nextSibling);
                    return true;
                }
            }
            else
            {
                //$node->removeChild($child);
                $child->setAttribute(ApiAttrs::REMOVE, 'true');
                $child->nodeValue = '';
            }
        }

        return false;
    }
}