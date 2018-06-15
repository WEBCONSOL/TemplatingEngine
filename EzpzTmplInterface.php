<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;

interface EzpzTmplInterface
{
    public function compile(Context $context, Tmpl $tmpl): string;
}