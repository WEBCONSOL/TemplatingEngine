<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\EzpzTmplInterface;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;

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