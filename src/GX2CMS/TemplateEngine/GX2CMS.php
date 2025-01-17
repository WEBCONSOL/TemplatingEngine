<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use Psr\Http\Message\RequestInterface;

if (!defined('GX2CMS_PLATFORM_TAG')) {include __DIR__ . '/constants.php';}

final class GX2CMS
{
    private $engine;

    public static function render(string $soruce, array $context, string $root="", string $resourceAbsPath="",
                                  \WC\Database\Driver $driver=null,
                                  RequestInterface $request=null, bool $isPage=false)
    {
        if ($resourceAbsPath) {
            Util\ClientLibs::searchClientlibByResource($resourceAbsPath);
        }
        $tmpl = new Tmpl($soruce, $resourceAbsPath);
        $tmpl->loadWholeDOC($isPage);
        $engine = new DefaultTemplate($driver, $request);
        $engine->setResourceRoot($root);
        return $engine->compile(new Context($context), $tmpl);
    }


    /**
     * Ezpz constructor.
     *
     * @param $engine
     */
    public function __construct(InterfaceEzpzTmpl $engine=null, \WC\Database\Driver $driver=null, RequestInterface $request=null)
    {
        if ($engine === null) {
            $this->loadEngine(new DefaultTemplate($driver, $request));
        }
        else if ($engine instanceof InterfaceEzpzTmpl) {
            $this->engine = $engine;
        }
        else {
            die("Compiling Error. Unknown templating engine.");
        }
    }

    /**
     * @param InterfacePlugin $plugin
     */
    public function addPluginToEngine(InterfacePlugin $plugin) {$this->engine->addPlugin($plugin);}

    /**
     * @param InterfaceEzpzTmpl $engine
     */
    public function loadEngine(InterfaceEzpzTmpl $engine) {$this->engine = $engine;}

    /**
     * @param Context $context
     * @param Tmpl    $tmpl
     *
     * @return string
     */
    public function compile(Context $context, Tmpl $tmpl): string
    {
        return $this->engine->compile($context, $tmpl);
    }

    public function getEngine(): InterfaceEzpzTmpl {return $this->engine;}
}