<?php

namespace GX2CMS\TemplateEngine\Model;

use GX2CMS\TemplateEngine\Util\StringUtil;
use WC\Utilities\PregUtil;

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
    public function __construct(string $var, string $partialsPath = "")
    {
        if (!defined('GX2CMS_PLATFORM_TAG')) {
            include dirname(__DIR__) . '/constants.php';
        }
        if ($partialsPath) {
            $this->partialsPath = $partialsPath;
        }
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

        $this->loadPartials();
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

    /**
     * @void load partials
     */
    private function loadPartials() {
        if ($this->content) {
            if ($this->partialsPath) {
                $pattern = '/\<'.GX2CMS_PLATFORM_TAG.' data-'.GX2CMS_PLATFORM_TAG.'-include="(.[^"]*)"><\/'.GX2CMS_PLATFORM_TAG.'\>/';
                $matches = PregUtil::getMatches($pattern, $this->content);
                if (sizeof($matches)) {
                    foreach ($matches[1] as $item) {
                        $includeTag = '<'.GX2CMS_PLATFORM_TAG.' data-'.GX2CMS_PLATFORM_TAG.'-include="'.$item.'"></'.GX2CMS_PLATFORM_TAG.'>';
                        $includeFile = $this->partialsPath . '/' . trim($item, '/');
                        if (file_exists($includeFile)) {
                            $includeContent = file_get_contents($includeFile);
                            $this->content = str_replace($includeTag, $includeContent, $this->content);
                        }
                    }
                }
            }
            $this->injectGlobalClientlibPlaceholder();
        }
    }

    /**
     * @void inject gx2cms-stylesheet-placeholder and gx2cms-javascript-placeholder
     */
    private function injectGlobalClientlibPlaceholder() {
        if (!StringUtil::contains($this->content, "gx2cms-stylesheet-placeholder")) {
            $this->content = str_replace('<'.'/head>','${gx2cms-stylesheet-placeholder}'."\n".'<'.'/head>', $this->content);
        }
        if (!StringUtil::contains($this->content, "gx2cms-javascript-placeholder")) {
            $this->content = str_replace('<'.'/body>','${gx2cms-javascript-placeholder}'."\n".'<'.'/body>', $this->content);
        }
    }
}