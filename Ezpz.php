<?php

namespace TemplateEngine;

use TemplateEngine\Model\Context;
use TemplateEngine\Model\Tmpl;

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
}