<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use WC\Utilities\PregUtil;

class CompileCall implements CompileInterface
{
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine): bool
    {
        $attrValue = $child->getAttribute(ApiAttrs::CALL);
        $pattern = '/\$\{clientlib\.(.[^\s]*)\\s+@\\s+categories=\'(.[^\']*)\'\}/';
        $matches = PregUtil::getMatches($pattern, $attrValue);
        if (!sizeof($matches)) {
            $pattern = '/\$\{clientlib\.(.[^\s]*)@categories=\'(.[^\']*)\'\}/';
            $matches = PregUtil::getMatches($pattern, $attrValue);
            if (!sizeof($matches)) {
                $pattern = '/\$\{clientlib\.(.[^\s]*)\\s+@categories=\'(.[^\']*)\'\}/';
                $matches = PregUtil::getMatches($pattern, $attrValue);
                if (!sizeof($matches)) {
                    $pattern = '/\$\{clientlib\.(.[^\s]*)@\\s+categories=\'(.[^\']*)\'\}/';
                    $matches = PregUtil::getMatches($pattern, $attrValue);
                }
            }
        }
        if (sizeof($matches)) {
            if (isset($matches[1]) && isset($matches[1][0])) {
                $ext = $matches[1][0];
                if (isset($matches[2]) && isset($matches[2][0])) {
                    $categories = explode(',', $matches[2][0]);
                    $placeholder = array();
                    foreach ($categories as $category) {
                        $placeholder[] = '{clientlib.category.'.$category.'.'.$ext.'}';
                    }
                    // INJECT PLACEHOLDER INTO HTML
                    // NEED TO REPLACE THE PLACEHOLDER WITH CONTENT STORED IN ClientLibs::getAggregateByCategory()
                    $child->nodeValue = implode('', $placeholder);
                }
            }
        }
        return true;
    }
}