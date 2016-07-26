<?php
namespace MercadoPago\Config;

abstract class AbstractConfig
{
    protected $data = null;

    protected $cache = [];

    public function __construct(array $data)
    {
        $this->data = array_merge($this->getDefaults(), $data);
    }

    protected function getDefaults()
    {
        return [];
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->data[$key];
        }

        return $default;
    }

    public function set($key, $value)
    {
        // Assign value at target node
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return (array_key_exists($key, $this->data));
    }

    public function all()
    {
        return $this->data;
    }
    
    public function configure ($data = []) {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

}