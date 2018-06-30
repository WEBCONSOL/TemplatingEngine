<?php

namespace GX2CMS\TemplateEngine\Model;

use GX2CMS\Lib\Util;

class Context implements \JsonSerializable, \Countable
{
    private $valueType = null;
    private $data = array();

    function __construct($val)
    {
        $this->reset($val);
    }

    public function reset($v=null)
    {
        if (is_array($v)) {
            $this->data = $v;
            $this->valueType = 'array';
        }
        else if (is_object($v)) {
            $this->data = json_decode(json_encode($v), true);
            $this->valueType = 'object';
        }
        else {
            $hasDS = sizeof(explode('/', $v)) || sizeof(explode('\\', $v));
            if ($hasDS && pathinfo($v, PATHINFO_EXTENSION) && file_exists($v)) {
                $this->data = json_decode(file_get_contents($v), true);
                $this->valueType = 'file';
            }
            else if (Util::isValidJSON($v)) {
                $this->data = json_decode($v, true);
                $this->valueType = 'JSONString';
            }
            else {
                $this->data = array($this->data);
                $this->valueType = 'string';
            }
        }
    }

    public function get(string $k, $default=null)
    {
        if (isset($this->data[$k])) {
            return $this->data[$k];
        }
        else if ($this->valueType === 'string') {
            return end($this->data);
        }
        return $default;
    }

    public function set(string $k, $v)
    {
        if ($k !== null) {
            $this->data[$k] = $v;
        }
    }

    public function remove()
    {
        $argc = func_get_args();
        if ($argc != null && sizeof($argc)) {
            foreach ($argc as $k) {
                if (isset($this->data[$k])) {
                    unset($this->data[$k]);
                }
            }
        }
    }

    public function has($k): bool
    {
        return $k !== null && isset($this->data[$k]);
    }

    public function is($k, $v): bool
    {
        return $k !== null && isset($this->data[$k]) && $this->data[$k] === $v;
    }

    public function first()
    {
        if (sizeof($this->data)) {
            return array_values($this->data)[0];
        }
        return null;
    }

    public function last()
    {
        if (sizeof($this->data)) {
            $arr = array_values($this->data);
            return end($arr);
        }
        return null;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function count(): int
    {
        return sizeof($this->data);
    }

    public function hasElement(): bool
    {
        return $this->count() > 0;
    }

    public function isEmpty(): bool
    {
        return !$this->hasElement();
    }

    public function getAsArray(): array
    {
        return $this->data;
    }

    public function __toString()
    {
        return json_encode($this->data);
    }
}