<?php
namespace MercadoPago\Http;

use Exception;

class CurlRequest
    implements HttpRequest
{
    private $handle = null;

    public function __construct($uri = null)
    {
        if (!extension_loaded("curl")) {
            throw new Exception("cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.");
        }
        $this->handle = curl_init($uri);

        return $this->handle;
    }

    public function setOption($name, $value)
    {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute()
    {
        return curl_exec($this->handle);
    }

    public function getInfo($name)
    {
        return curl_getinfo($this->handle, $name);
    }

    public function close()
    {
        curl_close($this->handle);
    }

    public function error()
    {
        return curl_error($this->handle);
    }
}