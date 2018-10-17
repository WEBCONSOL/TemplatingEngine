<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\Util;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\GX2CMS;
use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\StringUtil;
use WC\Utilities\CustomResponse;

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
        $this->reformatContext($context);
        $attrValue = $child->getAttribute(ApiAttrs::RESOURCE);
        $attrResource = $this->parseDataEzpzResource($attrValue);

        if ($attrResource['resource'] === GX2CMS_CONTAINER_PARAGRAPH_SYSTEM) {
            Util\ClientLibs::searchClientlibByResource(self::resourceAbsPath($engine, $attrResource['resource']));
            $buffer = array();
            $this->loadContainerParagraphSystem($attrResource, $child, $context, $tmpl, $engine, $buffer);
            $newNode = new \DOMText();
            $newNode->data = self::loadResource($attrResource['resource'], array('renderedResource' => implode('', $buffer)), $engine);
            $child->removeAttribute(ApiAttrs::RESOURCE);
            $child->appendChild($newNode);
        }
        else if ($this->resourceExistsInProperties($context, $attrResource)) {
            Util\ClientLibs::searchClientlibByResource(self::resourceAbsPath($engine, $attrResource['resource']));
            $this->loadResourcesFromProperties($attrResource, $child, $context, $tmpl, $engine);
        }
        else {
            $path = $attrResource['path'];
            $resource = $attrResource['resource'];
            if (!$path && !$resource && $attrValue) {$resource = $attrValue;}
            $selector = $child->hasAttribute(ApiAttrs::DATA_SELECTOR) ? $child->getAttribute(ApiAttrs::DATA_SELECTOR) : 'properties';
            if (StringUtil::startsWith($resource, $engine->getResourceRoot())) {
                $resource = str_replace(rtrim($engine->getResourceRoot(), '/'), '', $resource);
            }
            $resourceAbsPath = self::resourceAbsPath($engine, $resource);

            $last = pathinfo($resourceAbsPath, PATHINFO_BASENAME);
            $html = $resourceAbsPath . DS . $last . '.' . Util\FileExtension::HTML;
            $data = $resourceAbsPath . DS . 'data' . DS . $selector . '.' . Util\FileExtension::JSON;

            $hasHtml = file_exists($html);
            $hasData = file_exists($data);

            if ($hasHtml)
            {
                if (!$hasData) {
                    $dataFromContext = array();
                    $this->fetchData($path, trim($attrResource['resource'], '/'), $context->getAsArray(), $dataFromContext);
                    $data = $dataFromContext;
                }
                else {
                    $data = json_decode(file_get_contents($data), true);
                }
                Util\ClientLibs::searchClientlibByResource($resourceAbsPath);
                $newNode = new \DOMText();
                $newNode->data = self::loadResource($resource, $data, $engine);
                $child->removeAttribute(ApiAttrs::RESOURCE);
                $child->appendChild($newNode);
            }
            else if (trim($resource, '/') === ApiAttrs::PARSYS)
            {
                if ($context->has('parsys')) {
                    $parsys = $context->get('parsys');
                    if (is_array($parsys) && isset($parsys[$path])) {
                        $contentBuffer = array();
                        foreach ($parsys[$path] as $par) {
                            $resourceAbsPath = $engine->getResourceRoot() . trim($par, '/');
                            $last = pathinfo($resourceAbsPath, PATHINFO_BASENAME);
                            $html = $resourceAbsPath . DS . $last . '.' . Util\FileExtension::HTML;
                            $data = $resourceAbsPath . DS . 'data' . DS . $selector . '.' . Util\FileExtension::JSON;
                            if (file_exists($html)) {
                                Util\ClientLibs::searchClientlibByResource($resourceAbsPath);
                                $data = file_exists($data) ? json_decode(file_get_contents($data), true) : array();
                                $contentBuffer[] = self::loadResource($par, $data, $engine);
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

    public static function loadResource(string $resource, array $data, InterfaceEzpzTmpl &$engine): string {
        $resourceAbsPath = self::resourceAbsPath($engine, $resource);
        $last = pathinfo($resource, PATHINFO_FILENAME);
        $tmplFile = $resourceAbsPath.'/'.$last.'.html';
        $buffer = '';
        if (file_exists($tmplFile)) {
            $tmpl = new Tmpl($tmplFile, $resourceAbsPath);
            $tmpl->setPartialsPath($resourceAbsPath);
            if ($engine->hasDatabaseDriver() && $engine->hasRequest()) {
                $tmplEngine = new GX2CMS(null, $engine->getDatabaseDriver(), $engine->getRequest());
            }
            else if ($engine->hasDatabaseDriver()) {
                $tmplEngine = new GX2CMS(null, $engine->getDatabaseDriver());
            }
            else if ($engine->hasRequest()) {
                $tmplEngine = new GX2CMS(null, null, $engine->getRequest());
            }
            else {
                $tmplEngine = new GX2CMS();
            }
            self::formatData($data);
            $context = new Context($data);
            $tmplEngine->getEngine()->setResourceRoot($engine->getResourceRoot());
            if ($engine->hasResourceRoot()) {
                $tmplEngine->getEngine()->setResourceRoot($engine->getResourceRoot());
            }
            if ($engine->hasPlugins()) {
                $tmplEngine->getEngine()->setPlugins($engine->getPlugins());
            }
            $buffer = $tmplEngine->compile($context, $tmpl);
            $tmplEngine->getEngine()->invokePluginsWithResourcePath($resource, $buffer, $context, $tmpl);
        }
        else {
            CustomResponse::render(500, "Template file: $tmplFile does not exist");
        }
        return $buffer;
    }

    private function resourceExistsInProperties(Context &$context, array $attrResource): bool {
        $properties = null;
        if ($context->has('properties')) {
            $properties = $context->get('properties');
        }
        else if ($context->has('data')) {
            $properties = $context->get('data');
        }
        if (is_array($properties) && isset($properties[$attrResource['path']]) &&
            isset($properties[$attrResource['path']]['resourceType']) &&
            $properties[$attrResource['path']]['resourceType'] === $attrResource['resource']) {
            return true;
        }
        return false;
    }

    private function loadResourcesFromProperties(array $attrResource, \DOMElement &$child, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine) {
        $last = pathinfo($attrResource['resource'], PATHINFO_FILENAME);
        $resourceAbsPath = rtrim($engine->getResourceRoot(), '/') . '/' . trim($attrResource['resource'], '/');
        $properties = null;
        if ($context->has('properties')) {$properties = $context->get('properties');}
        else if ($context->has('data')) {$properties = $context->get('data');}
        if ($properties && isset($properties[$attrResource['path']])) {
            $properties = $properties[$attrResource['path']];
            if (isset($properties['properties'])) {$data = $properties['properties'];}
            else if (isset($properties['data'])) {$data = $properties['data'];}
            else {$data = array();}

            self::formatData($data);
            $contextData = $context->getAsArray();
            $contextData['properties'] = isset($data['properties']) ? $data['properties'] : $data;
            $newNode = new \DOMText();
            $newNode->data = self::loadResource($attrResource['resource'], $contextData, $engine);
            $child->removeAttribute(ApiAttrs::RESOURCE);
            $child->appendChild($newNode);
        }
    }

    /* @TODO: handle recursively */
    private function loadContainerParagraphSystem(array $attrResource, \DOMElement &$child, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine, array &$buffer) {
        $last = pathinfo($attrResource['resource'], PATHINFO_FILENAME);
        $resourceAbsPath = self::resourceAbsPath($engine, $attrResource['resource']);
        $properties = null;
        if ($context->has('properties')) {$properties = $context->get('properties');}
        else if ($context->has('data')) {$properties = $context->get('data');}
        if ($properties && isset($properties[$attrResource['path']])) {
            $properties = $properties[$attrResource['path']];
            if (isset($properties['properties'])) {$data = $properties['properties'];}
            else if (isset($properties['data'])) {$data = $properties['data'];}
            else {$data = array();}
            foreach ($data as $nodeName => $nodeProperty) {
                if (isset($nodeProperty['resourceType'])) {
                    $nodeContextData = isset($nodeProperty['data']) ? $nodeProperty['data'] : array();
                    self::formatData($nodeContextData);
                    $contextData = $context->getAsArray();
                    $contextData['properties'] = isset($nodeContextData['properties']) ? $nodeContextData['properties'] : $nodeContextData;
                    $buffer[] = self::loadResource($nodeProperty['resourceType'], $contextData, $engine);
                }
            }
        }
    }

    private function reformatContext(Context &$context) {
        $arr = $context->getAsArray();
        if (isset($arr['properties']) && isset($arr['id'])) {
            $id = $arr['id'];
            if (isset($arr['properties']['properties']) && isset($arr['properties']['id'])) {
                if ($id === $arr['properties']['id']) {
                    $arr['properties'] = $arr['properties']['properties'];
                    $context = new Context($arr);
                }
            }
        }
    }

    private static function formatData(array &$data) {
        $isArray = true;
        foreach ($data as $k=>$v) {
            if (!is_numeric($k)) {
                $isArray = false;
                break;
            }
        }
        if ($isArray) {
            $data = array('properties' => $data);
        }
    }

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

    private static function resourceAbsPath(InterfaceEzpzTmpl &$engine, string $resource): string {
        $root = $engine->getResourceRoot();
        if (StringUtil::startsWith($resource, $root)) {$resource = str_replace($root, '', $resource);}
        $path = rtrim($root, '/') . '/' . trim($resource, '/');
        return str_replace(array('////','///','//'), '/', $path);
    }
}