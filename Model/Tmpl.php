<?php

namespace GX2CMS\TemplateEngine\Model;

use GX2CMS\Lib\Util;

class Tmpl
{
    private $content;
    private $type = 'string';
    private $partialsPath = '';
    private $isDOC = false;
    private $root = '';
    private $webRoot = '';
    private $clientLibsPathFormat = '';

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

    public function hasPartialPath(): bool {return strlen($this->partialsPath) > 0;}

    public function setRoot(string $path) {$this->root = $path;}

    public function getRoot(): string {return $this->root;}

    public function hasRoot(): bool {return strlen($this->root) > 0;}

    public function setWebRoot(string $path) {$this->webRoot = $path;}

    public function getWebRoot(): string {return $this->webRoot;}

    public function hasWebRoot(): bool {return strlen($this->webRoot) > 0;}

    public function setClientlibsPathFormat(string $path) {$this->clientLibsPathFormat = $path;}

    public function getClientlibsPathFormat(): string {return $this->clientLibsPathFormat;}

    public function hasClientlibsPathFormat(): bool {return strlen($this->clientLibsPathFormat) > 0;}

    /**
     * @return string
     */
    public function __toString() {return $this->content;}
}