<?php

namespace GX2CMS\TemplateEngine\Util;

use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;
use GX2CMS\TemplateEngine\GX2CMS;
use GX2CMS\TemplateEngine\InterfaceEzpzTmpl;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use WC\Models\ListModel;

final class CompilerUtil
{
    private function __construct(){}

    // TODO: use in test api - optimize this.
    public static function conditionalExpressionTokenizer(string $str): array {
        $str =  preg_replace(RegexConstants::WHITESPACE, '', $str);
        $list = preg_split('/([\||&|=|!|\(|\)|<|>])/', $str);
        foreach ($list as $i=>$v) {
            if (!$v) {
                unset($list[$i]);
            }
        }
        $vars = array();
        $list = array_unique(array_values($list));
        foreach ($list as $var) {
            $vars[] = is_numeric($var) || strpos($var, "'") !== false || strpos($var, '"') !== false ? $var : '$'.$var;
        }
        $output = array('vars' => $list, 'statement' => str_replace($list, $vars, $str));
        return $output;
    }

    public static function getVarValue(Context &$context, array $vars) {
        $val = null;
        if ($context->hasElement() && !empty($vars)) {
            $n1 = sizeof($vars);
            $n2 = 0;
            foreach ($vars as $var) {
                if ($context->has($var)) {
                    $val = $context->get($var);
                    $n2++;
                }
                else if (is_array($val) && isset($val[$var])) {
                    $val = $val[$var];
                    $n2++;
                }
            }
            if ($n1 > $n2) {
                return null;
            }
        }
        return $val;
    }

    public static function openCloseHBTag(string $str): string {
        return str_replace(
            array(ApiAttrs::TAG_EZPZ_OPEN, ApiAttrs::TAG_EZPZ_CLOSE),
            array(ApiAttrs::TAG_HB_OPEN, ApiAttrs::TAG_HB_CLOSE),
            $str
        );
    }

    public static function isLiteral(string $str): bool {

        $str = trim($str);
        if (substr($str, 0, 2)===ApiAttrs::TAG_EZPZ_OPEN && $str[strlen($str)-1]===ApiAttrs::TAG_EZPZ_CLOSE) {
            return true;
        }
        else {
            $pattern = RegexConstants::LITERAL;
            $matches = PregUtil::getMatches($pattern, $str);
            if (!empty($matches)) {
                $splits = preg_split($pattern, $str);
                if (sizeof($splits) > 1) {
                    $reverseMatches = array_reverse($matches[0]);
                    $newSplits = array();
                    foreach ($splits as $i=>$split) {
                        $newSplits[] = $split;
                        if (!empty($reverseMatches)) {
                            $newSplits[] = array_pop($reverseMatches);
                        }
                    }
                    if (implode('', $newSplits) === $str) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function parseLiteral(string $data): array {
        return PregUtil::getMatches(RegexConstants::LITERAL, $data);
    }

    public static function loadContainerParagraphSystem(string $path, string $resource, Context &$context, InterfaceEzpzTmpl &$engine): string {
        $last = pathinfo($resource, PATHINFO_FILENAME);
        $resourceAbsPath = self::resourceAbsPath($engine, $resource);
        $properties = null;
        $data = null;
        if ($context->has('properties')) {$properties = $context->get('properties');}
        else if ($context->has('data')) {$properties = $context->get('data');}
        if ($context->has('cols')) {
            $data = $context->getAsArray();
        }
        else if (is_array($properties) && isset($properties[$path])) {
            $properties = $properties[$path];
            if (isset($properties['properties'])) {$data = $properties['properties'];}
            else if (isset($properties['data'])) {$data = $properties['data'];}
            else {$data = array();}
        }
        if ($data !== null) {
            if (isset($data['cols']) && isset($data['resources']) && is_array($data['resources']) && sizeof($data['resources'])) {
                $html = file_get_contents($resourceAbsPath.'/container.html');
                $pattern = '/<div class="col-(.*)"><gx2cms data-gx2cms-resource="(.*)"><\/gx2cms><\/div>/';
                $matches =  PregUtil::getMatches($pattern, $html);
                if (sizeof($matches) === 3) {
                    $base = $matches[0][0];
                    $pattern = array($matches[1][0], '<gx2cms data-gx2cms-resource="'.$matches[2][0].'"></gx2cms>');
                    $parts = explode('+', $data['cols']);
                    $buffer = array();
                    foreach ($parts as $i=>$col) {
                        if (isset($data['resources'][$i])) {
                            $resourceData = array('properties' => isset($data['resources'][$i]['data']) ? $data['resources'][$i]['data'] : array());
                            $replace = array($col, self::loadResource($data['resources'][$i]['resourceType'], $resourceData, $engine));
                            $buffer[] = str_replace($pattern, $replace, $base);
                        }
                    }
                    if (sizeof($buffer)) {
                        return GX2CMS::render(
                            str_replace(array($base, 'data-gx2cms-list="${cols}"'), array(implode('', $buffer), ''), $html),
                            array('cols'=>true)
                        );
                    }
                    else {
                        return GX2CMS::render(
                            str_replace(array($base, 'data-gx2cms-list="${cols}"'), array(implode('', $buffer), ''), $html),
                            array()
                        );
                    }
                }
            }
            else if (is_string($data)) {
                return GX2CMS::render($data, array(), $engine->getResourceRoot(), '');
            }
        }
        return '';
    }

    public static function loadResource(string $resource, array $data, InterfaceEzpzTmpl &$engine): string {
        $buffer = '';
        $resourceAbsPath = self::resourceAbsPath($engine, $resource);
        $last = pathinfo($resource, PATHINFO_FILENAME);
        $tmplFile = $resourceAbsPath.'/'.$last.'.html';
        if (file_exists($tmplFile)) {
            ClientLibs::searchClientlibByResource($resourceAbsPath);
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
            $context = new Context($data);
            $tmplEngine->getEngine()->setResourceRoot($engine->getResourceRoot());
            if ($engine->hasResourceRoot()) {
                $tmplEngine->getEngine()->setResourceRoot($engine->getResourceRoot());
            }
            if ($engine->hasPlugins()) {
                $tmplEngine->getEngine()->setPlugins($engine->getPlugins());
            }

            $metadata = $resourceAbsPath . '/'.$last.'.json';
            if (file_exists($metadata)) {
                $metadata = new ListModel(json_decode(file_get_contents($metadata), true));
                if ($metadata->has('authoringDialog')) {
                    $authoringDialog = new ListModel($metadata->get('authoringDialog'));
                    $config = array(
                        'metadata' => $metadata->getAsArray(),
                        'path' => $resource
                    );
                    $authoring = array();
                    if ($authoringDialog->has('type') && $authoringDialog->is('type', 'table')) {
                        $authoring[] = '<div data-authoring-type="table" data-config="'.base64_encode(json_encode($config)).'">';
                        $authoring[] = $tmplEngine->compile($context, $tmpl);
                        $authoring[] = '</'.'div>';
                    }
                    else {
                        $authoring[] = '<!--authoringtool_start'.json_encode($config).'-->';
                        $authoring[] = $tmplEngine->compile($context, $tmpl);
                        $authoring[] = '<!--authoringtool_end-->';
                    }
                    $buffer = implode('', $authoring);
                }
            }
            if (!$buffer) {
                $buffer = $tmplEngine->compile($context, $tmpl);
            }
            $tmplEngine->getEngine()->invokePluginsWithResourcePath($resource, $buffer, $context, $tmpl);
        }
        else {
            CustomResponse::render(500, "Template file: $tmplFile does not exist");
        }
        return $buffer;
    }

    public static function resourceAbsPath(InterfaceEzpzTmpl &$engine, string $resource): string {
        $root = $engine->getResourceRoot();
        if (StringUtil::startsWith($resource, $root)) {$resource = str_replace($root, '', $resource);}
        $path = rtrim($root, '/') . '/' . trim($resource, '/');
        return str_replace(array('////','///','//'), '/', $path);
    }

    public static function formatContextualData(array &$data) {
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
}