<?php

namespace Template;

use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;
use Masterminds\HTML5;
use Template\Model\Context;
use Template\Model\Tmpl;
use Template\DefaultTemplate\ApiAttrs;
use Template\DefaultTemplate\CompileInterface;
use Template\DefaultTemplate\CompileLiteral;
use Template\Util\CompilerUtil;
use Template\Util\Html5Util;
use Template\Util\NodeUtil;
use Template\Util\Tokenizer;

final class DefaultTemplate implements EzpzTmpl
{
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

        $this->_compileHtmlELements($buffer, $context);

        $this->loadEngine();
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
    private function _compile(HTML5 &$html5, &$node, Context $context, Tmpl $tmpl, EzpzTmpl $engine, array $apiServices)
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
                        foreach ($apiServices as $api => $service)
                        {
                            if ($child->hasAttributes() && $child->hasAttribute($api) || NodeUtil::hasApiAttr($child->attributes, $api))
                            {
                                $service = $this->tmplPackagePfx . $service;
                                $service = new $service;

                                if ($service instanceof CompileInterface)
                                {
                                    $passed = $service($node, $child, $context, $tmpl, $engine);
                                }
                            }
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

    private function _compileHtmlELements(&$buffer, Context $context) {
        $matches = array();
        preg_match_all('/\${(.[^}]*)}/', $buffer, $matches);
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

    private function loadEngine()
    {
        if (!(self::$engine instanceof Handlebars))
        {
            self::$engine = new Handlebars();

            $list = glob(__DIR__ . DS . 'Handlebars' . DS . 'Helper' . DS . '*.php');

            foreach ($list as $helper)
            {
                $parts = explode(DS, $helper);
                $last = str_replace('.php', '', end($parts));
                $cls = '\\Template\\Handlebars\\Helper\\' . $last;
                self::$engine->addHelper(str_replace('helper', '', strtolower($last)), new $cls);
            }
        }
    }
}