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

    const TEST = "data-".GX2CMS_PLATFORM_TAG."-test";
    const LIST = "data-".GX2CMS_PLATFORM_TAG."-list";
    const INCLUDE = "data-".GX2CMS_PLATFORM_TAG."-include";
    const RESOURCE = "data-".GX2CMS_PLATFORM_TAG."-resource";
    const USE = "data-".GX2CMS_PLATFORM_TAG."-use";
    const CALL = "data-".GX2CMS_PLATFORM_TAG."-call";

    const ELEMENT = "data-".GX2CMS_PLATFORM_TAG."-element";
    const ATTRIBUTE = "data-".GX2CMS_PLATFORM_TAG."-attribute";

    const API_SERVICES = array(
        self::TEST => "Test",
        self::LIST => "List",
        self::INCLUDE => "Partial",
        self::RESOURCE => "Resource",
        self::ATTRIBUTE => "Attribute",
        self::USE => "Use",
        self::CALL => "Call"
    );

    const API_LATELOADER_SERVICES = array(
        self::ELEMENT => "Element",
    );

    const PARSYS = "wcm/foundation/components/parsys";

    const DATA_SELECTOR = "data-selector";

    private function __construct(){}
}