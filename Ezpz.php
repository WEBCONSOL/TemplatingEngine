<?php

namespace Template;

use Template\Model\Context;
use Template\Model\Tmpl;

final class Ezpz
{
    private $engine;

    /**
     * Ezpz constructor.
     *
     * @param $engine
     */
    public function __construct(EzpzTmpl $engine=null)
    {
        if ($engine === null) {
            $this->loadEngine(new DefaultTemplate());
        }
        else if ($engine instanceof EzpzTmpl) {
            $this->engine = $engine;
        }
        else {
            die("Compiling Error. Unknown templating engine.");
        }
    }

    /**
     * @param EzpzTmpl $engine
     */
    public function loadEngine(EzpzTmpl $engine) {$this->engine = $engine;}

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