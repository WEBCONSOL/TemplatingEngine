<?php

namespace GX2CMS\TemplateEngine\Util;

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
            if ($attr instanceof \DOMAttr && StringHelper::startsWith($attr->nodeName, $attrName)) {
                return true;
            }
        }
        return false;
    }
}