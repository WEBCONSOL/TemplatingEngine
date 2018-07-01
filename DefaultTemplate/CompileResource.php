<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\Lib\FileExtension;
use GX2CMS\Lib\Response;
use GX2CMS\TemplateEngine\Ezpz;
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
        $arr = $this->parseDataEzpzResource($child->getAttribute(ApiAttrs::RESOURCE));
        $path = $arr['path'];
        $resource = $arr['resource'];
        $selector = $child->hasAttribute(ApiAttrs::DATA_SELECTOR) ? $child->getAttribute(ApiAttrs::DATA_SELECTOR) : 'properties';

        if (!StringUtil::startsWith($resource, $engine->getResourceRoot())) {
            $absRootPath = $engine->getResourceRoot() . trim($resource, '/');
        }
        else {
            $absRootPath = $resource;
            $resource = str_replace($engine->getResourceRoot(), '', $absRootPath);
        }

        $last = pathinfo($absRootPath, PATHINFO_BASENAME);
        $html = $absRootPath . DS . $last . '.' . FileExtension::HTML;
        $data = $absRootPath . DS . 'data' . DS . $selector . '.' . FileExtension::JSON;

        $hasHtml = file_exists($html);
        $hasData = file_exists($data);

        if ($hasHtml)
        {
            $newContext = new Context($data);
            $newTmpl = new Tmpl($html);
            $newTmpl->setPartialsPath($absRootPath);
            $templateEngine = new Ezpz();
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
            $node->insertBefore($newNode, $child->firstChild);
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
                        $html = $absRootPath . DS . $last . '.' . FileExtension::HTML;
                        $data = $absRootPath . DS . 'data' . DS . $selector . '.' . FileExtension::JSON;
                        if (file_exists($html) && file_exists($data)) {
                            $newContext = new Context($data);
                            $newTmpl = new Tmpl($html);
                            $newTmpl->setPartialsPath($absRootPath);
                            $templateEngine = new Ezpz();
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
        return true;
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
}