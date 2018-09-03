<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\Util\StringUtil;
use GX2CMS\TemplateEngine\Handlebars\Handlebars;
use GX2CMS\TemplateEngine\Handlebars\Loader\FilesystemLoader;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;
use GX2CMS\TemplateEngine\DefaultTemplate\CompileInterface;
use GX2CMS\TemplateEngine\Util\CompileLiteral;
use GX2CMS\TemplateEngine\Util\CompilerUtil;
use GX2CMS\TemplateEngine\Util\Html5Util;
use GX2CMS\TemplateEngine\Util\NodeUtil;
use GX2CMS\TemplateEngine\Handlebars\GX2CMContext;


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

        if (StringUtil::hasTag($tmplContent)) {

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
                        $this->compileLateLoader($html5, $node, $context, $tmpl, $this);
                    }
                }
            }

            if ($tmpl->isDOC()) {
                $buffer = Html5Util::formatOutput($html5, $dom, false);
            }
            else {
                $buffer = Html5Util::formatOutput($html5, $dom);
            }

            $buffer = CompileLiteral::getParsedData($context, $buffer);
        }
        else {
            $buffer = CompileLiteral::getParsedData($context, $tmplContent);
        }

        if ($tmpl->hasPartialsPath()) {
            $this->engine()->setPartialsLoader(new FilesystemLoader(
                $tmpl->getPartialsPath(),
                array(
                    'extension' => '.html'
                )
            ));
        }

        $buffer = $this->engine()->render($buffer, new GX2CMContext($context->getAsArray()));

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
    }

    /**
     * @param Context $context
     * @param string  $buffer
     */
    private function compileLateLoader(HTML5 &$html5, &$node, Context $context, Tmpl $tmpl, InterfaceEzpzTmpl $engine)
    {
        if ($node instanceof \DOMElement) {

            if ($node->hasChildNodes()) {

                foreach ($node->childNodes as $child) {

                    if ($child instanceof \DOMElement) {

                        foreach (ApiAttrs::API_LATELOADER_SERVICES as $api => $service) {

                            if ($child->hasAttribute($api) || NodeUtil::hasApiAttr($child->attributes, $api)) {

                                $service = $this->tmplPackagePfx . $service;
                                $service = new $service;
                                if ($service instanceof CompileInterface) {
                                    $service($node, $child, $context, $tmpl, $engine);
                                }
                            }
                        }

                        $this->compileLateLoader($html5, $child, $context, $tmpl, $engine);
                    }
                }
            }
        }
    }

    private function compileAttributeContext(Context &$context, \DOMNamedNodeMap &$attrs)
    {
        for ($i = 0; $i < $attrs->length; $i++) {
            $attr = $attrs->item($i);
            if (StringUtil::startsWith($attr->nodeName, ApiAttrs::ELEMENT)) {
                if (strlen(trim($attr->nodeValue))) {
                    $this->hasElementApiAttr = true;
                }
            }
            if (!StringUtil::startsWith($attr->nodeName, ApiAttrs::ELEMENT) &&
                $attr->nodeName !== ApiAttrs::RESOURCE &&
                NodeUtil::isNotApiAttr($attr->nodeName) &&
                CompilerUtil::isLiteral($attr->nodeValue)
            ) {
                $attr->nodeValue = CompileLiteral::getParsedData($context, trim($attr->nodeValue));
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
                $last = pathinfo($helper, PATHINFO_FILENAME);
                $cls = $this->handlebarsHelperPackage . '\\' . $last;
                $helperName = str_replace('helper', '', strtolower($last));
                if (!self::$engine->hasHelper($helperName)) {
                    if (!class_exists($cls, false)) {
                        include $helper;
                    }
                    self::$engine->addHelper($helperName, new $cls);
                }
            }
        }

        return self::$engine;
    }

    /**
     * @param string $buffer
     */
    private function sly2ezpz(string &$buffer) {
        $buffer = str_replace(array('<sly','</sly>','-sly-'), array('<ezpz','</ezpz>','-ezpz-'), $buffer);
    }

    /**
     * @param \DOMElement $child
     *
     * @return bool
     */
    private function ignore(\DOMElement &$child) {
        if ($child->nodeName === 'script') {
            if ($child->getAttribute('type') === 'text/x-handlebars-template') {
                return true;
            }
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
    public function setResourceRoot(string $resourceRoot){$this->resourceRoot = $resourceRoot;}

    /**
     * @return string
     */
    public function getResourceRoot(): string {return $this->resourceRoot ? (rtrim($this->resourceRoot, '/') . '/') : $this->resourceRoot;}

    /**
     * @return bool
     */
    public function hasResourceRoot(): bool {return strlen($this->resourceRoot) > 0;}

    /**
     * @return bool
     */
    public function hasPlugins(): bool {return sizeof($this->plugins) > 0;}
}