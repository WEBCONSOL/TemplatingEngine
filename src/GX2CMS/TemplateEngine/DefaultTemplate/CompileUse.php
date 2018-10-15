<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

use GX2CMS\TemplateEngine\Model\AbstractComponentModel;
use GX2CMS\TemplateEngine\Util;
use GX2CMS\TemplateEngine\Util\Response;
use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\InterfacePlugin;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use WC\Models\ListModel;

class CompileUse implements CompileInterface
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
        $var = '';
        $attrVal = '';

        foreach ($child->attributes as $attribute) {
            if ($attribute instanceof \DOMAttr && Util\StringUtil::startsWith($attribute->name, ApiAttrs::USE)) {
                $parts = explode('.', $attribute->name);
                if (sizeof($parts) > 1) {
                    $var = $parts[1];
                    $attrVal = preg_replace('/[\'\"\s\r\n\t]/', '', trim($attribute->nodeValue));
                    $child->removeAttribute($attribute->name);
                }
            }
        }
        if ($var && $attrVal)
        {
            $pattern = '/\${(.[^}]*)\}/';
            $matches = array();
            preg_match_all($pattern, trim($attrVal), $matches);
            if (sizeof($matches) > 1 && isset($matches[1]) && isset($matches[1][0]) && $matches[1][0]) {
                $attrVal = $matches[1][0];
            }
            $file = $tmpl->getPartialsPath() . '/data/' . $attrVal . '.' . Util\FileExtension::JSON;
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                $currentData = $context->getAsArray();
                $context->set($var, array_merge($currentData, $data));
                $plugins = $engine->getPlugins();
                foreach ($plugins as $plugin) {
                    if ($plugin instanceof InterfacePlugin) {
                        $plugin->processContext($context, $tmpl);
                    }
                }
            }
            else {
                $file = $engine->getResourceRoot() . '/' . str_replace('.', '/', $attrVal) . '.php';
                $cls = pathinfo($file, PATHINFO_FILENAME);
                $dataMissing = true;
                if (file_exists($file)) {
                    if (!class_exists($cls, false)) {
                        include $file;
                    }
                    if (is_subclass_of($cls, '\GX2CMS\TemplateEngine\Model\AbstractComponentModel')) {
                        if ($engine->hasDatabaseDriver()) {
                            $model = new $cls($engine->getDatabaseDriver());
                        }
                        else {
                            $model = new $cls();
                        }
                        if ($model instanceof AbstractComponentModel) {
                            if ($engine->hasRequest()) {
                                $model->process($engine->getRequest());
                            }
                            else {
                                $model->process();
                            }
                            $data = $model->response();
                            $currentData = $context->getAsArray();
                            $context->set($var, array_merge($currentData, $data->getAsArray()));
                            $dataMissing = false;
                        }
                        else {
                            Response::renderPlaintext('Resource data: ' . $attrVal . ' has to be either an instance of AbstractComponentModel or a json data');
                        }
                    }
                    else {
                        Response::renderPlaintext('Resource data: ' . $attrVal . ' has to be either an instance of AbstractComponentModel or a json data');
                    }
                }
                if ($dataMissing) {
                    Response::renderPlaintext('Resource data: ' . $attrVal . ' does not exist');
                }
            }
        }
        else
        {
            Response::renderPlaintext('Syntax error for Use API');
        }

        return true;
    }
}