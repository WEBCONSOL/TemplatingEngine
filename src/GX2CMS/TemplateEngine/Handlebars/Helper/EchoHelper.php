<?php

namespace GX2CMS\TemplateEngine\Handlebars\Helper;

use GX2CMS\TemplateEngine\Util\StringUtil;
use GX2CMS\TemplateEngine\Handlebars\Context;
use GX2CMS\TemplateEngine\Handlebars\Helper;
use GX2CMS\TemplateEngine\Handlebars\Template;

class EchoHelper implements Helper
{
    public function execute(Template $template, Context $context, $args, $source)
    {
        $parsedArgs = $template->parseArguments($args);
        $buffer = $context->get($parsedArgs[0]);
        StringUtil::formatHandlebarBuffer($buffer);
        return $buffer;
    }
}
