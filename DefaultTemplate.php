<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\Lib\Response;
use GX2CMS\Lib\Util;
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


final class DefaultTemplate implements InterfaceEzpzTmpl
{
    private $handlebarsHelperPackage = '\GX2CMS\TemplateEngine\Handlebars\Helper';
    private static $engine = null;
    private $tmplPackagePfx = null;
    private $hasElementApiAttr = false;
    private $plugins = array();
    private $resourceRoot = '';

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
        $tmplContent = $tmpl->getContent();
        $this->sly2ezpz($tmplContent);
        $this->invokePluginsToProcessContext($context, $tmpl);

        if ($this->hasTag($tmplContent)) {

            $html5 = new HTML5();
            $dom = $html5->loadHTML($tmplContent);

            foreach ($dom->childNodes as $node) {
                if ($node instanceof \DOMElement) {
                    $this->process($html5, $node, $context, $tmpl, $this, ApiAttrs::API_SERVICES);
                }
            }

            if ($this->hasElementApiAttr) {
                foreach ($dom->childNodes as $node) {
                    if ($node instanceof \DOMElement) {
                        $this->process($html5, $node, $context, $tmpl, $this, ApiAttrs::API_LATELOADER_SERVICES);
                    }
                }
            }

            foreach ($dom->childNodes as $node) {
                if ($node instanceof \DOMElement) {
                    $this->remove($dom, $node);
                }
            }

            if ($tmpl->isDOC()) {
                $buffer = Html5Util::formatOutput($html5, $dom, false);
            }
            else {
                $buffer = Html5Util::formatOutput($html5, $dom);
            }

            $this->compileHtmlElements($context, $buffer);
        }
        else {
            $buffer = CompileLiteral::getParsedData($context, $tmplContent);
        }

        $this->processRemaining($buffer, $context, $tmpl, $this);

        if ($tmpl->hasPartialsPath()) {
            $this->engine()->setPartialsLoader(new FilesystemLoader(
                $tmpl->getPartialsPath(),
                array(
                    'extension' => '.html'
                )
            ));
        }

        $buffer = $this->engine()->render(preg_replace('/}}}}+/', ApiAttrs::TAG_HB_CLOSE, $buffer), $context->getAsArray());

        unset($html5, $dom, $tmplContent);

        return $buffer;
    }

    /**
     * @param HTML5   $html5
     * @param Context $context
     * @param         $node
     */
    private function process(HTML5 &$html5, &$node, Context $context, Tmpl $tmpl, InterfaceEzpzTmpl $engine, array $apiServices) {

        if ($node instanceof \DOMElement) {

            if ($node->hasChildNodes()) {

                foreach ($node->childNodes as $child) {

                    $passed = true;

                    if ($child instanceof \DOMElement) {

                        if ($this->ignore($child)) {
                            $passed = false;
                        }
                        else if ($child->hasAttributes()) {

                            foreach ($apiServices as $api => $service) {

                                if ($child->hasAttribute($api) || NodeUtil::hasApiAttr($child->attributes, $api)) {

                                    $service = $this->tmplPackagePfx . $service;
                                    $service = new $service;
                                    if ($service instanceof CompileInterface) {
                                        $passed = $service($node, $child, $context, $tmpl, $engine);
                                    }
                                }
                            }

                            $this->compileAttributeContext($context, $child->attributes);
                        }
                    }

                    if ($passed) {
                        $this->process($html5, $child, $context, $tmpl, $engine, $apiServices);
                    }
                }
            }
        }
        else if ($node instanceof \DOMText) {

            CompileLiteral::process($node, $context, $tmpl, $engine);
        }
    }

    private function processRemaining(string &$buffer, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl $engine) {
        $keys = array_keys(ApiAttrs::API_SERVICES);
        $found = array();
        foreach ($keys as $key) {
            $pattern = '/'.$key.'="(.[^"]*)"/';
            $matches = array();
            preg_match_all($pattern, $buffer, $matches);
            if (CompilerUtil::matchesFound($matches)) {
                if (!isset($found[$key])) {
                    $found[$key] = array(array(), array());
                }
                foreach ($matches[1] as $i=>$match) {
                    if (!in_array($match, $found[$key][1])) {
                        $found[$key][0][] = $matches[0][$i];
                        $found[$key][1][] = $match;
                    }
                }
            }
        }

        if (sizeof($found)) {

            $html5 = new HTML5();
            $dom = $html5->loadHTML($buffer);
            foreach (ApiAttrs::API_SERVICES as $attr=>$service) {
                if (isset($found[$attr])) {
                    $service = $this->tmplPackagePfx . $service;
                    $service = new $service;
                    if ($service instanceof CompileInterface) {
                        foreach ($found[$attr][1] as $attrVal) {
                            $foundNode = null;
                            $this->fetchNode($dom, $attr, $attrVal, $foundNode);
                            if ($foundNode instanceof \DOMElement) {
                                $service($foundNode->parentNode, $foundNode, $context, $tmpl, $engine);
                            }
                        }
                    }
                }
            }

            $buffer = Util::removeHtmlComments(Html5Util::formatOutput($html5, $dom, false));
            $this->invokePluginsWithoutResourcePath($buffer, $context, $tmpl);
            unset($found, $html5, $dom);
        }
    }

    private function fetchNode(&$node, $attrName, $attrVal, &$foundNode) {
        if ($foundNode === null) {
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attribute) {
                    if ($attribute->nodeName === $attrName && $attribute->nodeValue === $attrVal) {
                        $foundNode = $node;
                        break;
                    }
                }
            }
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $childNode) {
                    $this->fetchNode($childNode, $attrName, $attrVal, $foundNode);
                }
            }
        }
    }

    /**
     * @param Context $context
     * @param string  $buffer
     */
    private function compileHtmlElements(Context &$context, string &$buffer)
    {
        $matches = CompilerUtil::parseLiteral($buffer);

        if (sizeof($matches) > 0 && isset($matches[0]) && isset($matches[1]) && !empty($matches[0]) && !empty($matches[1])) {

            foreach ($matches[1] as $k=>$v) {

                $v = trim(preg_replace('/@(.[^\']*)context=\'(.[^\']*)\'/', '', $v));
                if ($context->has($v)) {
                    $matches[1][$k] = $context->get($v);
                }
                else {
                    $v = CompilerUtil::getVarValue($context, explode('.', $v));
                    if ($v) {
                        $matches[1][$k] = $v;
                    }
                    else if (strpos($matches[1][$k], '@') === false) {
                        $matches[1][$k] = CompilerUtil::openCloseHBTag($matches[0][$k]);
                    }
                    else {
                        $matches[1][$k] = $matches[0][$k];
                    }
                }
            }
            $buffer = str_replace($matches[0], $matches[1], $buffer);
        }
    }

    private function compileAttributeContext(Context &$context, \DOMNamedNodeMap &$attrs)
    {
        for ($i = 0; $i < $attrs->length; $i++) {
            $attr = $attrs->item($i);
            if (Util::startsWith($attr->nodeName, ApiAttrs::ELEMENT)) {
                $this->hasElementApiAttr = true;
            }
            else if ($attr->nodeName !== ApiAttrs::REMOVE && $attr->nodeName !== ApiAttrs::RESOURCE &&
                NodeUtil::isNotApiAttr($attr->nodeName) && CompilerUtil::isLiteral($attr->nodeValue)
            ) {
                $attr->nodeValue = CompileLiteral::getParsedData($context, trim($attr->nodeValue));
            }
        }
    }

    /**
     * @param $parent
     * @param $node
     */
    private function remove(&$parent, &$node) {
        if ($node instanceof \DOMElement) {
            if ($node->hasAttribute(ApiAttrs::REMOVE)) {
                $parent->removeChild($node);
            }
            else if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $this->remove($node, $child);
                    }
                    else if ($child->nodeName === '#comment') {
                        $node->removeChild($child);
                    }
                }
            }
        }
    }

    private function engine(): Handlebars
    {
        if (!(self::$engine instanceof Handlebars))
        {
            self::$engine = new Handlebars();

            $list = glob(__DIR__ . DS . 'Handlebars' . DS . 'Helper' . DS . '*.php');

            foreach ($list as $helper)
            {
                $parts = explode(DS, $helper);
                $last = str_replace('.php', '', end($parts));
                $cls = $this->handlebarsHelperPackage . '\\' . $last;
                self::$engine->addHelper(str_replace('helper', '', strtolower($last)), new $cls);
            }
        }

        return self::$engine;
    }

    /**
     * @param string $buffer
     *
     * @return bool
     */
    private function hasTag(string &$buffer): bool
    {
        $noTag = strip_tags($buffer);
        return $noTag !== $buffer;
    }

    /**
     * @param string $buffer
     */
    private function sly2ezpz(string &$buffer) {
        $buffer = str_replace(array('<sly','</sly','-sly-'), array('<ezpz','</ezpz','-ezpz-'), $buffer);
    }

    /**
     * @param \DOMElement $child
     *
     * @return bool
     */
    private function ignore(\DOMElement &$child) {
        if ($child->nodeName === 'script' && $child->getAttribute('type') === 'text/x-handlebars-template') {
            return true;
        }
        return false;
    }

    /**
     * @param InterfacePlugin $plugin
     */
    public function addPlugin(InterfacePlugin $plugin){$this->plugins[] = $plugin;}

    /**
     * @return array
     */
    public function getPlugins(): array {return $this->plugins;}

    /**
     * @param array $plugins
     */
    public function setPlugins(array $plugins) {
        $allValid = true;
        foreach ($plugins as $plugin) {
            if (!($plugin instanceof InterfacePlugin)) {
                $allValid = false;
            }
        }
        if ($allValid) {
            $this->plugins = $plugins;
        }
        else {
            Response::renderPlaintext("Error. All plugin as to be instance of InterfacePlugin");
        }
    }

    /**
     * @param string  $resourcePath
     * @param string  $buffer
     * @param Context $context
     * @param Tmpl    $tmpl
     */
    public function invokePluginsWithResourcePath(string $resourcePath, string &$buffer, Context &$context, Tmpl &$tmpl)
    {
        if (sizeof($this->plugins)) {
            foreach ($this->plugins as $plugin) {
                if ($plugin instanceof InterfacePlugin) {
                    $plugin->processOutputWithResourcePath($resourcePath, $buffer, $context, $tmpl);
                }
            }
        }
    }

    /**
     * @param string  $buffer
     * @param Context $context
     * @param Tmpl    $tmpl
     */
    public function invokePluginsWithoutResourcePath(string &$buffer, Context &$context, Tmpl &$tmpl)
    {
        if (sizeof($this->plugins)) {
            foreach ($this->plugins as $plugin) {
                if ($plugin instanceof InterfacePlugin) {
                    $plugin->processOutputWithoutResourcePath($buffer, $context, $tmpl);
                }
            }
        }
    }

    /**
     * @param Context $context
     * @param Tmpl    $tmpl
     */
    public function invokePluginsToProcessContext(Context &$context, Tmpl &$tmpl) {
        if (sizeof($this->plugins)) {
            foreach ($this->plugins as $plugin) {
                if ($plugin instanceof InterfacePlugin) {
                    $plugin->processContext($context, $tmpl);
                }
            }
        }
    }

    /**
     * @param string $resourceRoot
     */
    public function setResourceRoot(string $resourceRoot)
    {
        $this->resourceRoot = $resourceRoot;
    }

    /**
     * @return string
     */
    public function getResourceRoot(): string {
        return $this->resourceRoot ? (rtrim($this->resourceRoot, '/') . '/') : $this->resourceRoot;
    }

    /**
     * @return bool
     */
    public function hasResourceRoot(): bool {return strlen($this->resourceRoot) > 0;}

    /**
     * @return bool
     */
    public function hasPlugins(): bool {return sizeof($this->plugins) > 0;}
}