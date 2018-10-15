<?php

namespace GX2CMS\TemplateEngine\Model;

abstract class AbstractComponentModel
{
    private $databaseDriver;

    public function __construct(\Database\Driver $driver=null) {$this->databaseDriver = $driver;}

    public function getDatabaseDrive(): \Database\Driver {return $this->databaseDriver;}

    public function hasDatabaseDriver(): bool {return $this->databaseDriver instanceof \Database\Driver;}

    abstract public function process(\Psr\Http\Message\RequestInterface $request=null);

    abstract public function response(): \WC\Models\ListModel;
}