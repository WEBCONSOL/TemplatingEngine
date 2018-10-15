<?php

namespace GX2CMS\TemplateEngine\Util;

final class ClientLibs
{
    private static $data = array('css'=>array(), 'js'=>array(), 'style'=>array(), 'script'=>array());
    private static $hashedData = array('css'=>array(), 'js'=>array());
    private static $dataByCategory = array();

    public static function getAggregatedClientlibByCategory(string $category=null, string $type=null): array {
        if ($category && isset(self::$dataByCategory[$category])) {
            if ($type && isset(self::$dataByCategory[$category][$type])) {
                return self::$dataByCategory[$category][$type];
            }
            return self::$dataByCategory[$category];
        }
        return self::$dataByCategory;
    }

    /**
     * Being invoked by 2 places (ONLY):
     * 1) GX2CMS\TemplateEngine\DefaultTemplate\CompileResource
     * 2) WC\CMS\Repository\AbstractUcr;
     *
     * @param string $resource
     */
    public static function searchClientlibByResource(string $resource) {
        $glob = glob($resource . '/*');
        foreach ($glob as $item) {
            if (is_dir($item)) {
                self::searchClientlibByResource($item);
            }
            else if (pathinfo($item, PATHINFO_BASENAME) === 'clientlib.json') {
                $data = json_decode(file_get_contents($item), true);
                if (sizeof($data) && isset($data['categories']) && is_array($data['categories']) && sizeof($data['categories'])) {
                    $dir = dirname($item);
                    if (file_exists($dir.'/css.txt')) {
                        foreach ($data['categories'] as $category) {
                            self::aggregateByCategory($category, 'css', $dir);
                        }
                    }
                    else if (file_exists($dir.'/style')) {
                        foreach ($data['categories'] as $category) {
                            self::aggregateByCategory($category, 'css', $dir);
                        }
                    }
                    if (file_exists($dir.'/js.txt')) {
                        foreach ($data['categories'] as $category) {
                            self::aggregateByCategory($category, 'js', $dir);
                        }
                    }
                    else if (file_exists($dir.'/script')) {
                        foreach ($data['categories'] as $category) {
                            self::aggregateByCategory($category, 'js', $dir);
                        }
                    }
                }
            }
        }
    }

    private static function aggregateByCategory(string $category, string $type, string $content) {
        if (!isset(self::$dataByCategory[$category])) {
            self::$dataByCategory[$category] = array();
        }
        if (!isset(self::$dataByCategory[$category][$type])) {
            self::$dataByCategory[$category][$type] = array();
        }
        if (!isset(self::$dataByCategory[$category][$type][md5($content)])) {
            self::$dataByCategory[$category][$type][md5($content)] = $content;
        }
    }

    public static function aggregateCSS(string $data) {
        if (!self::dataExists($data, 'css')) {
            $matches = PregUtil::getMatches('/(.*)\.css$/', $data);
            if (sizeof($matches) && isset($matches[1]) && isset($matches[1][0]) && $matches[1][0]) {
                self::$data['css'][] = $data;
            }
            else {
                self::$data['style'][] = $data;
            }
        }
    }

    public static function aggregateJS(string $data) {
        if (!self::dataExists($data, 'js')) {
            $matches = PregUtil::getMatches('/(.*)\.js$/', $data);
            if (sizeof($matches) && isset($matches[1]) && isset($matches[1][0]) && $matches[1][0]) {
                self::$data['js'][] = $data;
            }
            else {
                self::$data['script'][] = $data;
            }
        }
    }

    public static function getCSS(): string {
        $output = array();
        foreach (self::$data['css'] as $str) {
            $output[] = '<link href="'.$str.'" rel="stylesheet" type="text/css" />';
        }
        if (sizeof(self::$data['style'])) {
            $output[] = '<style type="text/css">';
            foreach (self::$data['style'] as $str) {
                $output[] = $str;
            }
            $output[] = '</style>';
        }
        return implode('', $output);
    }

    public static function getJS(): string {
        $output = array();
        foreach (self::$data['js'] as $str) {
            $output[] = '<script src="'.$str.'"></script>';
        }
        if (sizeof(self::$data['script'])) {
            $output[] = '<script>';
            foreach (self::$data['script'] as $str) {
                $output[] = $str;
            }
            $output[] = '</script>';
        }
        return implode('', $output);
    }

    private static function dataExists(string $data, string $key): bool {
        if (in_array(md5($data), self::$hashedData[$key])) {
            return true;
        }
        self::$hashedData[$key][] = md5($data);
        return false;
    }
}