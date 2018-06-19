<?php

namespace GX2CMS\TemplateEngine\Util;

use GX2CMS\TemplateEngine\DefaultTemplate\ApiAttrs;

final class NodeUtil
{
    private function __construct(){}

    /**
     * @param \DOMElement $oldNode
     * @param string      $name
     */
    public static function changeName(\DOMElement &$oldNode, string $name) {
        $newNode = $oldNode->ownerDocument->createElement($name);
        foreach ($oldNode->attributes as $attribute) {
            $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }
        while ($oldNode->firstChild) {
            $newNode->appendChild($oldNode->firstChild);
        }
        $oldNode->parentNode->replaceChild($newNode, $oldNode);
    }

    /**
     * @param \DOMNamedNodeMap $attrs
     * @param                  $attrName
     *
     * @return bool
     */
    public static function hasApiAttr(\DOMNamedNodeMap $attrs, $attrName): bool {

        foreach($attrs as $attr) {
            if ($attr instanceof \DOMAttr && StringUtil::startsWith($attr->nodeName, $attrName)) {
                return true;
            }
        }
        return false;
    }

    public static function isApiAttr(string $attr): bool {
        foreach (ApiAttrs::API_SERVICES as $api) {
            if ($api === $attr) {
                return true;
            }
            else if (StringUtil::startsWith($attr, $api)) {
                return true;
            }
        }
        return false;
    }

    public static function isNotApiAttr(string $attr): bool {return !self::isApiAttr($attr);}
}