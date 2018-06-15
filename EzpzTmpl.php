<?php

namespace Template;

use Template\Model\Context;
use Template\Model\Tmpl;

interface EzpzTmpl
{
    public function compile(Context $context, Tmpl $tmpl): string;
}