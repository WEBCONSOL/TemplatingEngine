<?php

namespace Template\DefaultTemplate;

use Template\EzpzTmpl;
use Template\Model\Context;
use Template\Model\Tmpl;

interface CompileInterface
{
    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmpl $engine): bool;
}