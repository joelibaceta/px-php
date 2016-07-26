<?php
namespace MercadoPago\Config;

interface ParserInterface
{
    public function parse($path);

    public function getSupportedExtensions();
}