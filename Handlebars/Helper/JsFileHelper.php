<?php

namespace GX2CMS\TemplateEngine\Handlebars\Helper;

use GX2CMS\TemplateEngine\Util\StringUtil;
use Handlebars\Context;
use Handlebars\Helper;
use Handlebars\Template;

class JsFileHelper implements Helper
{
    public function execute(Template $template, Context $context, $args, $source)
    {
        $parsedArgs = $template->parseArguments($args);
        if ($parsedArgs[0]) {
            return '<script src="'.$parsedArgs[0].'"></script>';
        }
        return '';
    }
}
