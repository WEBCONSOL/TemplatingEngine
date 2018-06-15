<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\EzpzTmplInterface;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\CompilerUtil;

class CompileTest implements CompileInterface
{
    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine): bool
    {
        $token = new Context(CompilerUtil::conditionalExpressionTokenizer(
            str_replace(array(ApiAttrs::TAG_EZPZ_OPEN,ApiAttrs::TAG_EZPZ_CLOSE), '', $child->getAttribute(ApiAttrs::TEST)))
        );
        $statement = $token->has('statement') ? $token->get('statement') : null;

        if ($token->has('vars') && !empty($statement))
        {
            foreach ($token->get('vars') as $var)
            {
                if ($context->has($var)) {
                    ${$var} = $context->get($var);
                } else {
                    $parts = explode('.', $var);
                    ${$var} = CompilerUtil::getVarValue($context, $parts);
                    if (!empty(${$var})) {
                        $newVarName = str_replace('.', '_', $var);
                        $statement = str_replace($var, $newVarName, $statement);
                        ${$newVarName} = true;
                    }
                    else if (sizeof($parts) > 1) {
                        $newVarName = str_replace('.', '_', $var);
                        $statement = str_replace($var, $newVarName, $statement);
                        ${$newVarName} = false;
                    }
                }
            }

            $child->removeAttribute(ApiAttrs::TEST);
            $eval = eval('return (' . $statement . ');');
            if (!$eval) {
                $child->setAttribute(ApiAttrs::REMOVE, 'true');
                $child->nodeValue = '';
                return false;
            } else {
                $newNode1 = new \DOMText();
                $newNode1->data = '';
                $node->insertBefore($newNode1, $child);
                $newNode2 = new \DOMText();
                $newNode2->data = '';
                $node->insertBefore($newNode2, $child->nextSibling);
                return true;
            }
        }
        else {
            $node->removeChild($child);
            return false;
        }
    }
}