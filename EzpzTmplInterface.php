<?php

namespace TemplateEngine;

use TemplateEngine\Model\Context;
use TemplateEngine\Model\Tmpl;

interface EzpzTmplInterface
{
    public function compile(Context $context, Tmpl $tmpl): string;
}