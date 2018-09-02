<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;

interface InterfacePlugin
{
    public function processOutputWithResourcePath(string $resource, string &$buffer, Context &$context, Tmpl &$tmpl);

    public function processOutputWithoutResourcePath(string &$buffer, Context &$context, Tmpl &$tmpl);

    public function processContext(Context &$context, Tmpl &$tmpl);
}