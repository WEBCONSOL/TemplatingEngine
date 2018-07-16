<?php

namespace GX2CMS\TemplateEngine\Handlebars\Helper;

use GX2CMS\TemplateEngine\Handlebars\Context;
use GX2CMS\TemplateEngine\Handlebars\Helper;
use GX2CMS\TemplateEngine\Handlebars\Template;

class I18nHelper implements Helper
{
    public function execute(Template $template, Context $context, $args, $source)
    {
        // TODO: pass i18n object to Context
        $parsedArgs = $template->parseArguments($args);
        if ($parsedArgs[0]) {
            return $parsedArgs[0];
        }
        return '';
    }
}
