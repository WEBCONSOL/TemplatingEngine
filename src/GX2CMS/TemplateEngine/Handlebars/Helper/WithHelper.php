<?php
/**
 * This file is part of Handlebars-php
 *
 * PHP version 5.3
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    fzerorubigd <fzerorubigd@gmail.com>
 * @author    Behrooz Shabani <everplays@gmail.com>
 * @author    Dmitriy Simushev <simushevds@gmail.com>
 * @author    Jeff Turcotte <jeff.turcotte@gmail.com>
 * @copyright 2014 Authors
 * @license   MIT <http://opensource.org/licenses/MIT>
 * @version   GIT: $Id$
 * @link      http://xamin.ir
 */

namespace GX2CMS\TemplateEngine\Handlebars\Helper;

use GX2CMS\TemplateEngine\Handlebars\Context;
use GX2CMS\TemplateEngine\Handlebars\Helper;
use GX2CMS\TemplateEngine\Handlebars\Template;

/**
 * The With Helper
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
class WithHelper implements Helper
{
    /**
     * Execute the helper
     *
     * @param \GX2CMS\TemplateEngine\Handlebars\Template  $template The template instance
     * @param \GX2CMS\TemplateEngine\Handlebars\Context   $context  The current context
     * @param \GX2CMS\TemplateEngine\Handlebars\Arguments $args     The arguments passed the the helper
     * @param string                $source   The source
     *
     * @return mixed
     */
    public function execute(Template $template, Context $context, $args, $source)
    {
        $positionalArgs = $args->getPositionalArguments();
        $context->with($positionalArgs[0]);
        $buffer = $template->render($context);
        $context->pop();

        return $buffer;
    }
}