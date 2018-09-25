<?php

namespace GX2CMS\TemplateEngine\Util;

final class ClientLibs
{
    private static $data = array('css'=>array(), 'js'=>array(), 'style'=>array(), 'script'=>array());
    private static $hashedData = array('css'=>array(), 'js'=>array());

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