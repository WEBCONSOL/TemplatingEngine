<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\Util;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\GX2CMS;
use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use GX2CMS\TemplateEngine\Util\StringUtil;
use WC\Models\ListModel;
use WC\Utilities\CustomResponse;
use WC\Utilities\PregUtil;

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
        $path = $attrResource['path'];
        $resource = $attrResource['resource'];

        if ($resource === GX2CMS_CONTAINER_PARAGRAPH_SYSTEM) {
            $newNode = new \DOMText();
            $newNode->data = Util\CompilerUtil::loadContainerParagraphSystem($path, $resource, $context,$engine);
            $child->removeAttribute(ApiAttrs::RESOURCE);
            $child->appendChild($newNode);
        }
        else if ($this->resourceExistsInProperties($path, $resource, $context)) {
            $newNode = new \DOMText();
            $newNode->data = $this->loadResourcesFromProperties($path, $resource, $child, $context, $tmpl, $engine);
            $child->removeAttribute(ApiAttrs::RESOURCE);
            $child->appendChild($newNode);
        }
        else {
            if (!$path && !$resource && $attrValue) {$resource = $attrValue;}
            $selector = $child->hasAttribute(ApiAttrs::DATA_SELECTOR) ? $child->getAttribute(ApiAttrs::DATA_SELECTOR) : 'properties';
            if (StringUtil::startsWith($resource, $engine->getResourceRoot())) {
                $resource = str_replace(rtrim($engine->getResourceRoot(), '/'), '', $resource);
            }
            $resourceAbsPath = Util\CompilerUtil::resourceAbsPath($engine, $resource);

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
                $newNode = new \DOMText();
                $newNode->data = Util\CompilerUtil::loadResource($resource, $data, $engine);
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
                                $data = file_exists($data) ? json_decode(file_get_contents($data), true) : array();
                                $contentBuffer[] = Util\CompilerUtil::loadResource($par, $data, $engine);
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

    private function resourceExistsInProperties(string $path, string $resource, Context &$context): bool {
        $properties = null;
        if ($context->has('properties')) {
            $properties = $context->get('properties');
        }
        else if ($context->has('data')) {
            $properties = $context->get('data');
        }
        if (is_array($properties) && isset($properties[$path]) &&
            isset($properties[$path]['resourceType']) &&
            $properties[$path]['resourceType'] === $resource) {
            return true;
        }
        return false;
    }

    private function loadResourcesFromProperties(string $path, string $resource, \DOMElement &$child, Context &$context, Tmpl &$tmpl, InterfaceEzpzTmpl &$engine): string {
        $last = pathinfo($resource, PATHINFO_FILENAME);
        $resourceAbsPath = rtrim($engine->getResourceRoot(), '/') . '/' . trim($resource, '/');
        $properties = null;
        if ($context->has('properties')) {$properties = $context->get('properties');}
        else if ($context->has('data')) {$properties = $context->get('data');}
        if (is_array($properties) && isset($properties[$path])) {
            $properties = $properties[$path];
            if (isset($properties['properties'])) {$data = $properties['properties'];}
            else if (isset($properties['data'])) {$data = $properties['data'];}
            else {$data = array();}
            Util\CompilerUtil::formatContextualData($data);
            $contextData = $context->getAsArray();
            $contextData['properties'] = isset($data['properties']) ? $data['properties'] : $data;
            return Util\CompilerUtil::loadResource($resource, $contextData, $engine);
        }
        else if (is_string($properties)) {
            return GX2CMS::render($properties, array(), $engine->getResourceRoot(), '');
        }
        return '';
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