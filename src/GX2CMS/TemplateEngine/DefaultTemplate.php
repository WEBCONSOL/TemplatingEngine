<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\HTML5\Elements;
use GX2CMS\TemplateEngine\Util\ClientLibs;
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
use WC\Utilities\PregUtil;


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
        $customElements = array(GX2CMS_PLATFORM_TAG, GX2CMS_INJECT_CSS, GX2CMS_INJECT_JS);
        foreach ($customElements as $element) {
            if (!isset(Elements::$html5[$element])) {
                Elements::$html5[$element] = 1;
            }
        }
    }

    /**
     * @param Context $context
     * @param Tmpl    $tmpl
     *
     * @return string
     */
    public function compile(Context $context, Tmpl $tmpl): string
    {
        $this->mergeHttpRequestData($context);
        $this->mergeSessionData($context);
        $tmplContent = $tmpl->getContent();
        $this->techtag2gx2cmstag($tmplContent);
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
                $buffer = str_replace(
                    array('${'.GX2CMS_STYLESHEET_PLACEHOLDER.'}', '${'.GX2CMS_JAVASCRIPT_PLACEHOLDER.'}'),
                    array('{{{'.GX2CMS_STYLESHEET_PLACEHOLDER.'}}}', '{{{'.GX2CMS_JAVASCRIPT_PLACEHOLDER.'}}}'),
                    $buffer
                );
                $context->set(GX2CMS_STYLESHEET_PLACEHOLDER, ClientLibs::getCSS());
                $context->set(GX2CMS_JAVASCRIPT_PLACEHOLDER, ClientLibs::getJS());
            }
            else {
                $buffer = Html5Util::formatOutput($html5, $dom);
            }

            $buffer = CompileLiteral::getParsedData($context, $buffer);
        }
        else {
            $buffer = CompileLiteral::getParsedData($context, $tmplContent);
        }

        if ($tmplContent && $buffer) {
            $matches = PregUtil::getMatches('/\<(.[^>]*)>/', $tmplContent);
            if (sizeof($matches)) {
                $parts = explode($matches[0][0], $tmplContent);
                $firstPart = $parts[0];
                if ($firstPart && $firstPart === strip_tags($firstPart)) {
                    $buffer = CompileLiteral::getParsedData($context, $firstPart) . $buffer;
                }
            }
        }

        foreach ($dom->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                $this->process($html5, $node, $context, $tmpl, $this, ApiAttrs::API_SERVICES);
            }
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

        if ($tmpl->isDOC()) {
            $buffer = str_replace(
                array('${'.TMPL_CLIENTLIB_ROOT.'}', '${'.COMP_CLIENTLIB_ROOT.'}'),
                array($context->get(TMPL_CLIENTLIB_ROOT, ""), $context->get(COMP_CLIENTLIB_ROOT, "")),
                $buffer
            );
        }

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

                        if ($this->ignore($child, $context)) {
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
    private function techtag2gx2cmstag(string &$buffer) {
        $buffer = str_replace(
            array('<'.GX2CMS_TECHNOLOGY_SLY_TAG,'</'.GX2CMS_TECHNOLOGY_SLY_TAG.'>','-'.GX2CMS_TECHNOLOGY_SLY_TAG.'-'),
            array('<'.GX2CMS_PLATFORM_TAG,'</'.GX2CMS_PLATFORM_TAG.'>','-'.GX2CMS_PLATFORM_TAG.'-'),
            $buffer);
    }

    /**
     * @param \DOMElement $child
     *
     * @return bool
     */
    private function ignore(\DOMElement &$child, Context &$context) {
        if ($child->nodeName === 'script') {
            if ($child->getAttribute('type') === 'text/x-handlebars-template') {
                $var = 'hb' . md5($child->nodeValue);
                $context->set($var, $child->nodeValue);
                $child->nodeValue = '{{{'.$var.'}}}';
                return true;
            }
        }
        else if ($child->nodeName === GX2CMS_INJECT_CSS) {
            ClientLibs::aggregateCSS(trim($child->nodeValue));
            $child->nodeValue = "";
            return true;
        }
        else if ($child->nodeName === GX2CMS_INJECT_JS) {
            ClientLibs::aggregateJS(trim($child->nodeValue));
            $child->nodeValue = "";
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

    public function mergeHttpRequestData(Context &$context) {
        $key = 'httpRequest';
        $data = array();
        if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
            $data = $_GET;
        }
        else if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
            $data = $_POST;
        }
        if (!$context->has($key) && sizeof($data)) {
            $context->set($key, $data);
        }
    }

    public function mergeSessionData(Context &$context) {
        $key = 'session_user_data';
        $data = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        if (!$context->has($key) && sizeof($data)) {
            $context->set($key, $data);
        }
    }
}