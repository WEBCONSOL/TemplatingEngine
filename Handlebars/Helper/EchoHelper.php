<?php

namespace TemplateEngine\Handlebars\Helper;

use Handlebars\Context;
use Handlebars\Helper;
use Handlebars\Template;

class EchoHelper implements Helper
{
    public function execute(Template $template, Context $context, $args, $source)
    {
        $parsedArgs = $template->parseArguments($args);
        $tmp = $context->get($parsedArgs[0]);
        return $tmp;
    }
}
