<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\Lib\FileExtension;
use GX2CMS\Lib\Response;
use GX2CMS\TemplateEngine\EzpzTmplInterface;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;

class CompileResource implements CompileInterface
{
    /**
     * @param Context     $context
     * @param \DOMElement $node
     * @param \DOMElement $child
     *
     * @return bool
     */
    public function __invoke(\DOMElement &$node, \DOMElement &$child, Context $context, Tmpl $tmpl, EzpzTmplInterface $engine): bool
    {
        $selector = $child->hasAttribute('selector') ? $child->getAttribute('selector') : 'properties';
        $resource = $this->parseDataEzpzResource($child->getAttribute(ApiAttrs::RESOURCE));
        $absRootPath = $this->parseDataEzpzResource($resource);
        if (!file_exists($absRootPath)) {
            $absRootPath = $tmpl->getRoot() . '/' . ltrim($resource, '/');
        }

        $resource = str_replace($tmpl->getRoot(), '', $absRootPath);

        $last = pathinfo($absRootPath, PATHINFO_BASENAME);
        $html = $absRootPath . DS . $last . '.' . FileExtension::HTML;
        $data = $absRootPath . DS . 'data' . DS . $selector . '.' . FileExtension::JSON;

        if (file_exists($html))
        {
            $newContext = new Context($data);

            $newTmpl = new Tmpl($html);
            $newTmpl->setPartialsPath($absRootPath);
            if ($tmpl->hasRoot()) {
                $newTmpl->setRoot($tmpl->getRoot());
            }

            $css = '';
            $js = '';
            if ($tmpl->hasClientlibsPathFormat()) {
                $newTmpl->setClientlibsPathFormat($tmpl->getClientlibsPathFormat());
                $css = sprintf($tmpl->getClientlibsPathFormat(), 'css', $resource);
                $js = sprintf($tmpl->getClientlibsPathFormat(), 'js', $resource);
            }

            $buffer = $engine->compile($newContext, $newTmpl);

            if ($js && !$engine->hasScript($js)) {
                $buffer .= '<script src="'.$js.'"></script>';
            }
            if ($css && !$engine->hasStyle($css)) {
                $buffer .= '<link rel="stylesheet" href="'.$css.'" type="text/css" />';
            }

            if ($child->nodeName === 'ezpz') {
                $newNode = new \DOMText();
                $newNode->data = $buffer;
                $node->replaceChild($newNode, $child);
            }
            else {
                $child->removeAttribute(ApiAttrs::RESOURCE);
                $newNode = new \DOMText();
                $newNode->data = $buffer;
                $child->insertBefore($newNode, $child->firstChild);
            }
        }
        else {
            Response::renderAsJSON(400, 'Bad request: resource ' . $resource . ' does not exist');
        }
        return true;
    }

    private function parseDataEzpzResource(string $str): string
    {
        $parts1 = explode('@', $str);
        $parts2 = explode('=', $parts1[sizeof($parts1)-1]);
        $path = str_replace(array("'", '"', '}', '{'), '', $parts2[sizeof($parts2)-1]);
        return '/' . ltrim($path, '/');
    }
}