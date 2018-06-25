<?php

namespace GX2CMS\TemplateEngine\Model;

use GX2CMS\Lib\Util;

class Tmpl
{
    private $content;
    private $type = 'string';
    private $partialsPath = '';
    private $isDOC = false;

    /**
     * Tmpl constructor.
     *
     * @param string $var
     */
    public function __construct(string $var)
    {
        if (file_exists($var))
        {
            $this->content = Util::removeHtmlComments(file_get_contents($var));
            $this->type = 'file';
        }
        else
        {
            $this->content = Util::removeHtmlComments($var);
        }
    }

    /**
     * @return string
     */
    public function getContent(): string {return $this->content;}

    /**
     * @return bool
     */
    public function isNotEmpty(): bool {return !$this->isEmpty();}

    /**
     * @return bool
     */
    public function isEmpty(): bool {return empty($this->content);}

    /**
     * @param string $path
     */
    public function setPartialsPath(string $path) {$this->partialsPath = $path;}

    public function loadWholeDOC(bool $isDoc) {$this->isDOC = $isDoc;}

    public function isDOC(): bool {return $this->isDOC;}

    /**
     * @return bool
     */
    public function hasPartialsPath(): bool {return !empty($this->partialsPath);}

    /**
     * @return string
     */
    public function getPartialsPath(): string {return rtrim($this->partialsPath, '/') . '/';}

    /**
     * @return string
     */
    public function __toString() {return $this->content;}
}