<?php
namespace MercadoPago\Http;
interface HttpRequest
{
    public function setOption($name, $value);
    public function execute();
    public function getInfo($name);
    public function close();
    public function error();
}