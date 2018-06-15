<?php

namespace GX2CMS\TemplateEngine\Handlebars\Helper;

use Handlebars\Context;
use Handlebars\Helper;
use Handlebars\Template;

class ForEachHelper implements Helper
{
    public function execute(Template $template, Context $context, $args, $source)
    {
        $positionalArgs = $args->getPositionalArguments();
        $tmp = $context->get($positionalArgs[0]);
        $buffer = '';

        if (!$tmp) {
            $template->setStopToken('else');
            $template->discard();
            $template->setStopToken(false);
            $buffer = $template->render($context);
        } elseif (is_array($tmp) || $tmp instanceof \Traversable) {
            $isList = is_array($tmp) && (array_keys($tmp) === range(0, count($tmp) - 1));
            $index = 0;
            $lastIndex = $isList ? (count($tmp) - 1) : false;

            foreach ($tmp as $key => $var) {
                $specialVariables = array(
                    '@index' => $index,
                    '@first' => ($index === 0),
                    '@last' => ($index === $lastIndex),
                );
                if (!$isList) {
                    $specialVariables['@key'] = $key;
                }
                $context->pushSpecialVariables($specialVariables);
                $context->push($var);
                $template->setStopToken('else');
                $template->rewind();
                $buffer .= $template->render($context);
                $context->pop();
                $context->popSpecialVariables();
                $index++;
            }

            $template->setStopToken(false);
        }

        return $buffer;
    }
}
