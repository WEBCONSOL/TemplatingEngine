<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\Util;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\GX2CMS;
use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\StringUtil;

class CompileResource implements CompileInterface
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
        $attrValue = $child->getAttribute(ApiAttrs::RESOURCE);
        $attrResource = $this->parseDataEzpzResource($attrValue);

        if ($this->resourceExistsInProperties($context, $attrResource)) {
            Util\ClientLibs::searchClientlibByResource(rtrim($engine->getResourceRoot(), '/').
                '/'. ltrim($attrResource['resource'], '/'));
            $this->loadResourcesFromProperties($attrResource, $child, $context, $tmpl, $engine);
        }
        else {
            $path = $attrResource['path'];
            $resource = $attrResource['resource'];

            if (!$path && !$resource && $attrValue) {$resource = $attrValue;}
            $selector = $child->hasAttribute(ApiAttrs::DATA_SELECTOR) ? $child->getAttribute(ApiAttrs::DATA_SELECTOR) : 'properties';

            if (!StringUtil::startsWith($resource, $engine->getResourceRoot())) {
                $absRootPath = $engine->getResourceRoot() . trim($resource, '/');
            }
            else {
                $absRootPath = $resource;
                $resource = str_replace($engine->getResourceRoot(), '', $absRootPath);
            }

            Util\ClientLibs::searchClientlibByResource(rtrim($engine->getResourceRoot(), '/').
                '/'. ltrim($attrResource['resource'], '/'));

            $last = pathinfo($absRootPath, PATHINFO_BASENAME);
            $html = $absRootPath . DS . $last . '.' . Util\FileExtension::HTML;
            $data = $absRootPath . DS . 'data' . DS . $selector . '.' . Util\FileExtension::JSON;

            $hasHtml = file_exists($html);
            $hasData = file_exists($data);

            if ($hasHtml)
            {
                if (!$hasData) {
                    $dataFromContext = array();
                    $this->fetchData($path, trim($attrResource['resource'], '/'), $context->getAsArray(), $dataFromContext);
                    $data = json_encode($dataFromContext);
                }

                $newContext = new Context($data);
                $newTmpl = new Tmpl($html);
                $newTmpl->setPartialsPath($absRootPath);
                $templateEngine = new GX2CMS();
                if ($engine->hasResourceRoot()) {
                    $templateEngine->getEngine()->setResourceRoot($engine->getResourceRoot());
                }
                if ($engine->hasPlugins()) {
                    $templateEngine->getEngine()->setPlugins($engine->getPlugins());
                }

                $buffer = $templateEngine->compile($newContext, $newTmpl);
                $templateEngine->getEngine()->invokePluginsWithResourcePath($resource, $buffer, $newContext, $newTmpl);

                $newNode = new \DOMText();
                $newNode->data = $buffer;

                $child->removeAttribute(ApiAttrs::RESOURCE);
                //$node->insertBefore($newNode, $child->firstChild);
                $child->appendChild($newNode);
            }
            else if (trim($resource, '/') === ApiAttrs::PARSYS)
            {
                if ($context->has('parsys')) {
                    $parsys = $context->get('parsys');
                    if (is_array($parsys) && isset($parsys[$path])) {
                        $contentBuffer = array();
                        foreach ($parsys[$path] as $par) {
                            $absRootPath = $engine->getResourceRoot() . trim($par, '/');
                            $last = pathinfo($absRootPath, PATHINFO_BASENAME);
                            $html = $absRootPath . DS . $last . '.' . Util\FileExtension::HTML;
                            $data = $absRootPath . DS . 'data' . DS . $selector . '.' . Util\FileExtension::JSON;
                            if (file_exists($html) && file_exists($data)) {
                                $newContext = new Context($data);
                                $newTmpl = new Tmpl($html);
                                $newTmpl->setPartialsPath($absRootPath);
                                $templateEngine = new GX2CMS();
                                if ($engine->hasResourceRoot()) {
                                    $templateEngine->getEngine()->setResourceRoot($engine->getResourceRoot());
                                }
                                if ($engine->hasPlugins()) {
                                    $templateEngine->getEngine()->setPlugins($engine->getPlugins());
                                }
                                $buffer = $templateEngine->compile($newContext, $newTmpl);
                                $templateEngine->getEngine()->invokePluginsWithResourcePath($resource, $buffer, $newContext, $newTmpl);
                                $contentBuffer[] = $buffer;
                            } else {
                                Response::renderPlaintext('Your resource ' . $par . ' loaded by the parsys does not exist');
                            }
                        }

                        $newNode = new \DOMText();
                        $newNode->data = implode('', $contentBuffer);
                        $child->removeAttribute(ApiAttrs::RESOURCE);
                        $child->insertBefore($newNode, $child->firstChild);
                    }
                    else {
                        Response::renderPlaintext('Your parsys ('.$resource.') is empty');
                    }
                }
                else {
                    Response::renderPlaintext('Your parsys ('.$resource.') is empty');
                }
            }
            else if (!$hasHtml) {
                Response::renderPlaintext('Bad request: resource ' . str_replace($engine->getResourceRoot(), '', $html) . ' does not exist');
            }
            else if (!$hasData) {
                Response::renderPlaintext('Bad request: resource data ' . str_replace($engine->getResourceRoot(), '', $data) . ' does not exist');
            }
        }
        return true;
    }

    private function resourceExistsInProperties(Context &$context, array $attrResource): bool {
        $properties = $context->get('properties');
        if (isset($properties[$attrResource['path']]) && isset($properties[$attrResource['path']]['resourceType']) &&
            $properties[$attrResource['path']]['resourceType'] === $attrResource['resource']) {
            return true;
        }
        return false;
    }

    private function loadResourcesFromProperties(array $attrResource, \DOMElement &$child, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine) {
        $last = pathinfo($attrResource['resource'], PATHINFO_FILENAME);
        $dir = rtrim($engine->getResourceRoot(), '/') . '/' . ltrim($attrResource['resource'], '/');
        $templateFile = $dir . '/' . $last . '.html';
        if (file_exists($templateFile)) {
            $properties = $context->get('properties');
            $data = isset($properties[$attrResource['path']]['data']) ? $properties[$attrResource['path']]['data'] : array();
            if (sizeof($data)) {
                $newContext = new Context($data);
            }
            else {
                $newContext = $context;
            }
            $newTmpl = new Tmpl($templateFile, $dir);
            if ($engine->hasDatabaseDriver() && $engine->hasRequest()) {
                $newTmplEngine = new GX2CMS(null, $engine->getDatabaseDriver(), $engine->getRequest());
            }
            else if ($engine->hasDatabaseDriver()) {
                $newTmplEngine = new GX2CMS(null, $engine->getDatabaseDriver());
            }
            else if ($engine->hasRequest()) {
                $newTmplEngine = new GX2CMS(null, null, $engine->getRequest());
            }
            else {
                $newTmplEngine = new GX2CMS();
            }
            $newTmplEngine->getEngine()->setResourceRoot($engine->getResourceRoot());

            $buffer = $newTmplEngine->compile($newContext, $newTmpl);
            $newTmplEngine->getEngine()->invokePluginsWithResourcePath($attrResource['resource'], $buffer, $newContext, $newTmpl);
            $newNode = new \DOMText();
            $newNode->data = $buffer;
            $child->removeAttribute(ApiAttrs::RESOURCE);
            $child->appendChild($newNode);
        }
    }

    /**
     * @param string $str
     *
     * @return array
     */
    private function parseDataEzpzResource(string $str): array
    {
        $pattern = '/\${\'(.[^}]*)\'(.[^}]*)@(.[^}]*)resourceType=\'(.[^}]*)\'}/';
        $matches = array();
        preg_match_all($pattern, $str, $matches);
        if (sizeof($matches) > 3 && isset($matches[1][0]) && $matches[1][0]) {
            return array('path' => $matches[1][0], 'resource' => $matches[sizeof($matches)-1][0]);
        }
        return array('path'=>'', 'resource'=>'');
    }

    private function fetchData(string $path, string $resource, array $data, array &$dataFromContext) {
        if (!sizeof($dataFromContext)) {
            foreach ($data as $key=>$item) {
                if ($key === $path && isset($item['resourceType']) && isset($item['data']) && $resource === trim($item['resourceType'],'/')) {
                    $dataFromContext['data'] = $item['data'];
                    break;
                }
                else if (is_array($item) && sizeof($item)) {
                    $this->fetchData($path, $resource, $item, $dataFromContext);
                }
            }
        }
    }
}