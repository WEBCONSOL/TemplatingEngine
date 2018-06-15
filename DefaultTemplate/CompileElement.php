<?php

namespace TemplateEngine\DefaultTemplate;

use TemplateEngine\EzpzTmplInterface;
use TemplateEngine\Model\Context;
use TemplateEngine\Model\Tmpl;
use TemplateEngine\Util\NodeUtil;

class CompileElement implements CompileInterface
{
    private $allowedElements = array('div', 'p', 'span', 'em');

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