<?php

namespace GX2CMS\TemplateEngine;

use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use Psr\Http\Message\RequestInterface;

interface InterfaceEzpzTmpl
{
    public function __construct(\WC\Database\Driver $driver=null, RequestInterface $request=null);

    public function compile(Context $context, Tmpl $tmpl): string;

    public function addPlugin(InterfacePlugin $plugin);

    public function invokePluginsWithResourcePath(string $resourcePath, string &$buffer, Context &$context, Tmpl &$tmpl);

    public function invokePluginsWithoutResourcePath(string &$buffer, Context &$context, Tmpl &$tmpl);

    public function invokePluginsToProcessContext(Context &$context, Tmpl &$tmpl);

    public function setResourceRoot(string $resourceRoot);

    public function getResourceRoot(): string;

    public function hasResourceRoot(): bool;

    public function getPlugins(): array;

    public function setPlugins(array $plugins);

    public function hasPlugins(): bool;

    public function setDatabaseDriver(\WC\Database\Driver $driver);

    public function getDatabaseDriver(): \WC\Database\Driver;

    public function hasDatabaseDriver(): bool;

    public function setRequest(RequestInterface $request);

    public function getRequest(): RequestInterface;

    public function hasRequest(): bool;
}