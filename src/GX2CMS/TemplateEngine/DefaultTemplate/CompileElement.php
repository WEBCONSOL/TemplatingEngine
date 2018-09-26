<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\NodeUtil;

class CompileElement implements CompileInterface
{
    private $allowedElements = array('div', 'p', 'span', 'em', 'i', 'h1', 'h2', 'h3', 'h4', 'h5');

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
        $attr = $child->getAttribute(ApiAttrs::ELEMENT);
        if ($attr) {
            $matches = CompilerUtil::parseLiteral($attr);
            if (sizeof($matches)) {
                $attr = $context->has($matches[1][0]) ? $context->has($matches[1][0]) : CompilerUtil::getVarValue($context, explode('.', $matches[1][0]));
            }

            if ($attr) {
                if (in_array($attr, $this->allowedElements)) {
                    $child->removeAttribute(ApiAttrs::ELEMENT);
                    NodeUtil::changeName($child, $attr);
                }
                else {
                    die("Compiling Error. Tag name: " . $attr . ' is not allowed. Allowed tag names are: ' . implode(', ', $this->allowedElements));
                }
            }
        }

        return true;
    }
}