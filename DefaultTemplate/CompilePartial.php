<?php

namespace Template\DefaultTemplate;

use Template\EzpzTmpl;
use Template\Model\Context;
use Template\Model\Tmpl;

class CompilePartial implements CompileInterface
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
        $attr = $child->getAttribute(ApiAttrs::INCLUDE);
        $file = $tmpl->getPartialsPath() . $attr;
        if (file_exists($file)) {
            $partialTmpl = new Tmpl($file);
            $partialTmpl->setPartialsPath($tmpl->getPartialsPath());
            $child->removeAttribute(ApiAttrs::INCLUDE);
            $newNode1 = new \DOMText();
            $newNode1->data = $engine->compile($context, $partialTmpl);
            $child->insertBefore($newNode1, $child->firstChild);
        }
        else {
            die("Compiling Error. Partial: " . $attr . ' does not exist');
        }
        return true;
    }
}