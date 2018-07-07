<?php

namespace GX2CMS\TemplateEngine\Handlebars\Helper;

use GX2CMS\TemplateEngine\Util\StringUtil;
use Handlebars\Context;
use Handlebars\Helper;
use Handlebars\Template;

/**
 * Handlebars halper interface
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    fzerorubigd <fzerorubigd@gmail.com>
 * @author    Behrooz Shabani <everplays@gmail.com>
 * @author    Dmitriy Simushev <simushevds@gmail.com>
 * @author    Jeff Turcotte <jeff.turcotte@gmail.com>
 * @copyright 2014 Authors
 * @license   MIT <http://opensource.org/licenses/MIT>
 * @version   Release: @package_version@
 * @link      http://xamin.ir
 */
class LtEqHelper implements Helper
{
    public function execute(Template $template, Context $context, $args, $source)
    {
        $parsedArgs = $template->parseArguments($args);
        $tmp1 = $context->get($parsedArgs[0]);
        $tmp2 = $context->get($parsedArgs[1]);

        if ($tmp1 <= $tmp2) {
            $template->setStopToken('else');
            $buffer = $template->render($context);
            $template->setStopToken(false);
            $template->discard($context);
        } else {
            $template->setStopToken('else');
            $template->discard($context);
            $template->setStopToken(false);
            $buffer = $template->render($context);
        }

        StringUtil::formatHandlebarBuffer($buffer);

        return $buffer;
    }
}
