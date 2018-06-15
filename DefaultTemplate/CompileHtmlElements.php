<?php

namespace TemplateEngine\DefaultTemplate;

use TemplateEngine\EzpzTmplInterface;
use TemplateEngine\Model\Context;
use TemplateEngine\Model\Tmpl;
use TemplateEngine\Util\CompilerUtil;

class CompileHtmlElements implements CompileInterface
{
    private $allowedAttrs = array('class', 'style', 'onclick', 'onblur', 'onkeypress', 'onkeyup', 'id');

    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine): bool
    {
        foreach ($child->attributes as $attribute) {
            if ($attribute instanceof \DOMAttr) {
                $parts = explode('.', $attribute->name);
                if (sizeof($parts) > 1 && in_array($parts[1], $this->allowedAttrs)) {
                    $attrVal = $attribute->nodeValue;
                    if (CompilerUtil::isLiteral($attrVal)) {
                        $attrVal = CompilerUtil::openCloseHBTag($attrVal);
                    }
                    $child->setAttribute($parts[1], $attrVal);
                    $child->removeAttribute($attribute->name);
                }
            }
        }
        return true;
    }
}