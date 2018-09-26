<?php

namespace GX2CMS\TemplateEngine\Util;

final class Constants {

    const SCHEMA_HTTP = "http://";
    const SCHEMA_HTTPS = "https://";

    const PATTERNS = array("!", "=", "&", "|", ">", "<", "'", '"', "(", ")");
    const REPLACES = array(GX2CMS_NEGATE_SIGN, GX2CMS_EQ_SIGN, GX2CMS_AND_SIGN, GX2CMS_OR_SIGN, GX2CMS_GT_SIGN, GX2CMS_LT_SIGN, GX2CMS_SINGLE_QUOTE, GX2CMS_DOUBLE_QUOTE, GX2CMS_BRACKET_OPEN, GX2CMS_BRACKET_CLOSE);

    const PATTERNS_WITH_WHITESPACE = array("!", "=", "&", "|", ">", "<", "'", '"', "(", ")", " ");
    const REPLACES_WITH_WHITESPACE = array(GX2CMS_NEGATE_SIGN, GX2CMS_EQ_SIGN, GX2CMS_AND_SIGN, GX2CMS_OR_SIGN, GX2CMS_GT_SIGN, GX2CMS_LT_SIGN, GX2CMS_SINGLE_QUOTE, GX2CMS_DOUBLE_QUOTE, GX2CMS_BRACKET_OPEN, GX2CMS_BRACKET_CLOSE, "");

    private function __construct(){}
}