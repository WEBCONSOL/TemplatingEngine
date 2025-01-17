<?php
/**
 * This file is part of Handlebars-php
 *
 * PHP version 5.3
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    Jeff Turcotte <jeff.turcotte@gmail.com>
 * @copyright 2014 Authors
 * @license   MIT <http://opensource.org/licenses/MIT>
 * @version   GIT: $Id$
 * @link      http://xamin.ir
 */

namespace GX2CMS\TemplateEngine\Handlebars;

/**
 * Handlebars helper interface
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    Jeff Turcotte <jeff.turcotte@gmail.com>
 * @copyright 2014 Authors
 * @license   MIT <http://opensource.org/licenses/MIT>
 * @version   Release: @package_version@
 * @link      http://xamin.ir
 */
interface Helper
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
    public function execute(Template $template, Context $context, $args, $source);
}
