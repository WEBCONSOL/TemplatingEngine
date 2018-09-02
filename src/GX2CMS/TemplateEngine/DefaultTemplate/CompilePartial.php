<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;

class CompilePartial implements CompileInterface
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