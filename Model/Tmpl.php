<?php

namespace GX2CMS\TemplateEngine\Model;

use GX2CMS\TemplateEngine\Util\StringUtil;

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
        $this->reset($var);
    }

    public function reset(string $var)
    {
        $hasDS = sizeof(explode('/', $var)) || sizeof(explode('\\', $var));

        if ($hasDS && pathinfo($var, PATHINFO_EXTENSION) === 'html' && file_exists($var))
        {
            $this->content = StringUtil::removeHtmlComments(file_get_contents($var));
            $this->type = 'file';
        }
        else
        {
            $this->content = StringUtil::removeHtmlComments($var);
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
     * @param bool $isDoc
     */
    public function loadWholeDOC(bool $isDoc) {$this->isDOC = $isDoc;}

    /**
     * @return bool
     */
    public function isDOC(): bool {return $this->isDOC;}

    /**
     * @param string $path
     */
    public function setPartialsPath(string $path) {$this->partialsPath = $path;}

    /**
     * @return bool
     */
    public function hasPartialsPath(): bool {return !empty($this->partialsPath);}

    /**
     * @return string
     */
    public function getPartialsPath(): string {return rtrim($this->partialsPath, '/') . '/';}

    /**
     * @return bool
     */
    public function hasPartialPath(): bool {return strlen($this->partialsPath) > 0;}

    /**
     * @return string
     */
    public function __toString() {return $this->content;}
}