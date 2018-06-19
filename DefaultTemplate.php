<?php

namespace GX2CMS\TemplateEngine;

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;
use Masterminds\HTML5;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;
use GX2CMS\TemplateEngine\DefaultTemplate\CompileInterface;
use GX2CMS\TemplateEngine\DefaultTemplate\CompileLiteral;
use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\Html5Util;
use GX2CMS\TemplateEngine\Util\NodeUtil;

final class DefaultTemplate implements EzpzTmplInterface
{
    const HANDLEBARS_HELPERS_PACKATE = '\\GX2CMS\\TemplateEngine\\Handlebars\\Helper\\';
    private static $engine = null;
    private $tmplPackagePfx = null;

    /**
     * DefaultTemplate constructor.
     */
    public function __construct()
    {
        $this->tmplPackagePfx = '\\'.get_class($this).'\\Compile';
    }

    /**
     * @param Context $context
     * @param Tmpl    $tmpl
     *
     * @return string
     */
    public function compile(Context $context, Tmpl $tmpl): string
    {
        $html5 = new HTML5();
        $dom = $html5->loadHTML($tmpl);

        foreach ($dom->childNodes as $node)
        {
            $this->_compile($html5, $node, $context, $tmpl, $this, ApiAttrs::API_SERVICES);
        }

        foreach ($dom->childNodes as $node)
        {
            $this->_compile($html5, $node, $context, $tmpl, $this, ApiAttrs::API_LATELOADER_SERVICES);
        }

        foreach ($dom->childNodes as $node)
        {
            $this->_remove($dom, $node);
        }

        if ($tmpl->isDOC()) {
            $buffer = Html5Util::formatOutput($html5, $dom, false);
        }
        else {
            $buffer = Html5Util::formatOutput($html5, $dom);
        }

        $this->_compileHtmlElements($buffer, $context);

        $this->_loadEngine();
        $engine =& self::$engine;
        if ($engine instanceof Handlebars) {
            if ($tmpl->hasPartialsPath()) {
                $engine->setPartialsLoader(new FilesystemLoader(
                    $tmpl->getPartialsPath(),
                    array(
                        'extension' => '.html'
                    )
                ));
            }
            return $engine->render(preg_replace('/}}}}+/', ApiAttrs::TAG_HB_CLOSE, $buffer), $context->getAsArray());
        }

        return $buffer;
    }

    /**
     * @param HTML5   $html5
     * @param Context $context
     * @param         $node
     */
    private function _compile(HTML5 &$html5, &$node, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine, array $apiServices)
    {
        if ($node instanceof \DOMElement)
        {
            if ($node->hasChildNodes())
            {
                foreach ($node->childNodes as $child)
                {
                    $passed = true;
                    if ($child instanceof \DOMElement)
                    {
                        if ($child->hasAttributes())
                        {
                            foreach ($apiServices as $api => $service)
                            {
                                if ($child->hasAttribute($api) || NodeUtil::hasApiAttr($child->attributes, $api))
                                {
                                    $service = $this->tmplPackagePfx . $service;
                                    $service = new $service;
                                    if ($service instanceof CompileInterface)
                                    {
                                        $passed = $service($node, $child, $context, $tmpl, $engine);
                                    }
                                }
                            }

                            $this->_compileAttributeContext($context, $child->attributes);
                        }
                    }
                    if ($passed)
                    {
                        $this->_compile($html5, $child, $context, $tmpl, $engine, $apiServices);
                    }
                }
            }
        }
        else if ($node instanceof \DOMText)
        {
            CompileLiteral::process($node, $context, $tmpl, $engine);
        }
    }

    /**
     * @param         $buffer
     * @param Context $context
     */
    private function _compileHtmlElements(&$buffer, Context $context) {
        $matches = CompilerUtil::parseLiteral($buffer);
        if (sizeof($matches) > 0 && isset($matches[0]) && isset($matches[1]) && !empty($matches[0]) && !empty($matches[1])) {
            foreach ($matches[1] as $k=>$v) {
                $v = trim(preg_replace('/@\s+context=\'(.[^\']*)\'/', '', $v));
                if ($context->has($v)) {
                    $matches[1][$k] = $context->get($v);
                }
                else {
                    $v = CompilerUtil::getVarValue($context, explode('.', $v));
                    if ($v) {
                        $matches[1][$k] = $v;
                    }
                }
            }
            $buffer = str_replace($matches[0], $matches[1], $buffer);
        }
    }

    private function _compileAttributeContext(Context $context, \DOMNamedNodeMap &$attrs)
    {
        for ($i = 0; $i < $attrs->length; $i++) {
            $attr = $attrs->item($i);
            if (NodeUtil::isNotApiAttr($attr->nodeName) && CompilerUtil::isLiteral($attr->nodeValue)) {
                $attr->nodeValue = CompileLiteral::getParsedData($context, trim($attr->nodeValue));
            }
        }
    }

    /**
     * @param $parent
     * @param $node
     */
    private function _remove(&$parent, &$node) {
        if ($node instanceof \DOMElement) {
            if ($node->hasAttribute(ApiAttrs::REMOVE)) {
                $parent->removeChild($node);
            }
            else if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $this->_remove($node, $child);
                    }
                }
            }
        }
    }

    private function _loadEngine()
    {
        if (!(self::$engine instanceof Handlebars))
        {
            self::$engine = new Handlebars();

            $list = glob(__DIR__ . DS . 'Handlebars' . DS . 'Helper' . DS . '*.php');

            foreach ($list as $helper)
            {
                $parts = explode(DS, $helper);
                $last = str_replace('.php', '', end($parts));
                $cls = self::HANDLEBARS_HELPERS_PACKATE . $last;
                self::$engine->addHelper(str_replace('helper', '', strtolower($last)), new $cls);
            }
        }
    }
}