<?php

namespace GX2CMS\TemplateEngine\DefaultTemplate;

class ApiAttrs
{
    const TAG_HB_OPEN = '{{';
    const TAG_HB_CLOSE = '}}';
    const TAG_HB_CTX_OPEN = '{{{';
    const TAG_HB_CTX_CLOSE = '}}}';
    const TAG_EZPZ_OPEN = '${';
    const TAG_EZPZ_CLOSE = '}';
    const EZPZ_LIST_ITEM = 'item.';
    const HB_LIST_ITEM = 'this.';

    const TEST = "data-ezpz-test";
    const LIST = "data-ezpz-list";
    const INCLUDE = "data-ezpz-include";
    const RESOURCE = "data-ezpz-resource";
    const USE = "data-ezpz-use";

    const ELEMENT = "data-ezpz-element";
    const ATTRIBUTE = "data-ezpz-attribute";

    const API_SERVICES = array(
        self::TEST => "Test",
        self::LIST => "List",
        self::INCLUDE => "Partial",
        self::RESOURCE => "Resource",
        self::ATTRIBUTE => "Attribute",
        self::USE => "Use"
    );

    const API_LATELOADER_SERVICES = array(
        self::ELEMENT => "Element",
    );

    const PARSYS = "wcm/foundation/components/parsys";

    const DATA_SELECTOR = "data-selector";

    private function __construct(){}
}