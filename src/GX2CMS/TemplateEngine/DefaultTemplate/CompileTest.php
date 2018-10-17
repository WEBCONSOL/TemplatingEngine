<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\Constants;

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
        $attrVal = $child->getAttribute(ApiAttrs::TEST);
        $matches = CompilerUtil::parseLiteral($attrVal);
        if (sizeof($matches)) {
            $child->removeAttribute(ApiAttrs::TEST);
            $matches[1][0] = str_replace(Constants::PATTERNS_WITH_WHITESPACE, Constants::REPLACES_WITH_WHITESPACE, $matches[1][0]);
            $newNode1 = new \DOMText();
            $newNode1->data = '{{#if ' . $matches[1][0] . '}}';
            $node->insertBefore($newNode1, $child);
            $newNode2 = new \DOMText();
            $newNode2->data = '{{/if}}';
            $node->insertBefore($newNode2, $child->nextSibling);
        }
        return true;
    }
}