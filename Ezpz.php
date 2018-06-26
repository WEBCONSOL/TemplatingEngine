<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;

final class Ezpz
{
    private $engine;

    /**
     * Ezpz constructor.
     *
     * @param $engine
     */
    public function __construct(EzpzTmplInterface $engine=null)
    {
        if ($engine === null) {
            $this->loadEngine(new DefaultTemplate());
        }
        else if ($engine instanceof EzpzTmplInterface) {
            $this->engine = $engine;
        }
        else {
            die("Compiling Error. Unknown templating engine.");
        }
    }

    /**
     * @param EzpzTmplInterface $engine
     */
    public function loadEngine(EzpzTmplInterface $engine) {$this->engine = $engine;}

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

    public function getEngine(): EzpzTmplInterface {return $this->engine;}
}