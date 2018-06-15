<?php

namespace TemplateEngine\DefaultTemplate;

use TemplateEngine\EzpzTmplInterface;
use TemplateEngine\Model\Context;
use TemplateEngine\Model\Tmpl;

interface CompileInterface
{
    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine): bool;
}