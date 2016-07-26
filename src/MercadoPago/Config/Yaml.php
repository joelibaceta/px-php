<?php
namespace MercadoPago\Config;
use Exception;
use Symfony\Component\Yaml\Yaml as YamlParser;

class Yaml implements ParserInterface
{
    public function parse($path)
    {
        try {
            $data = YamlParser::parse(file_get_contents($path));
        } catch (Exception $exception) {
            throw new Exception(
                array(
                    'message'   => 'Error parsing YAML file',
                    'exception' => $exception,
                )
            );
        }
        return $data;
    }

    public function getSupportedExtensions()
    {
        return array('yaml', 'yml');
    }
}