<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

class ApiAttrs
{
    const TAG_HB_OPEN = '{{';
    const TAG_HB_CLOSE = '}}';
    const TAG_EZPZ_OPEN = '${';
    const TAG_EZPZ_CLOSE = '}';
    const EZPZ_LIST_ITEM = 'item.';
    const HB_LIST_ITEM = 'this.';

    const TEST = "data-ezpz-test";
    const LIST = "data-ezpz-list";
    const INCLUDE = "data-ezpz-include";
    const ELEMENT = "data-ezpz-element";
    const ATTRIBUTE = "data-ezpz-attribute";
    const RESOURCE = "data-ezpz-resource";

    const REMOVE = "data-ezpz-remove";

    const API_SERVICES = array(
        self::TEST => "Test",
        self::LIST => "List",
        self::INCLUDE => "Partial",
        self::ATTRIBUTE => "Attribute",
        self::RESOURCE => "Resource"
    );

    const API_LATELOADER_SERVICES = array(
        self::ELEMENT => "Element"
    );

    private function __construct(){}
}