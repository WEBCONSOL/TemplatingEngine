<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\EzpzTmplInterface;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\NodeUtil;

class CompileElement implements CompileInterface
{
    private $allowedElements = array('div', 'p', 'span', 'em', 'i');

    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine): bool
    {
        $attr = $child->getAttribute(ApiAttrs::ELEMENT);
        if (in_array($attr, $this->allowedElements)) {
            $child->removeAttribute(ApiAttrs::ELEMENT);
            NodeUtil::changeName($child, $attr);
        }
        else {
            die("Compiling Error. Tag name: " . $attr . ' is not allowed. Allowed tag names are: ' . implode(', ', $this->allowedElements));
        }
        return true;
    }
}