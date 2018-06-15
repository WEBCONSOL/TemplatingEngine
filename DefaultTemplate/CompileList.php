<?php

namespace Template\DefaultTemplate;

use Template\EzpzTmpl;
use Template\Model\Context;
use Template\Model\Tmpl;

class CompileList implements CompileInterface
{
    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmpl $engine): bool
    {
        $attr = $child->getAttribute(ApiAttrs::LIST);
        $child->removeAttribute(ApiAttrs::LIST);
        $newNode1 = new \DOMText();
        $newNode1->data = str_replace(
            array(ApiAttrs::TAG_EZPZ_OPEN,ApiAttrs::TAG_EZPZ_CLOSE),
            array(ApiAttrs::TAG_HB_OPEN.'#foreach ', ApiAttrs::TAG_HB_CLOSE),
            $attr
        );
        $child->insertBefore($newNode1, $child->firstChild);
        $newNode2 = new \DOMText();
        $newNode2->data = ApiAttrs::TAG_HB_OPEN.'/foreach'.ApiAttrs::TAG_HB_CLOSE;
        $child->insertBefore($newNode2);
        return true;
    }
}