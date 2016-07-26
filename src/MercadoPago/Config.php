<?php
namespace MercadoPago;

use Exception;

class Config
    extends Config\AbstractConfig
{
    private $supportedFileParsers = array(
        'MercadoPago\\Config\\Json',
        'MercadoPago\\Config\\Yaml',
    );

    protected function getDefaults()
    {
        return ['base_url'      => 'https://api.mercadopago.com',
                'CLIENT_ID'     => '',
                'CLIENT_SECRET' => '',
                'APP_ID'        => '',
                'ACCESS_TOKEN'  => '',
                'REFRESH_TOKEN' => '',
                'sandbox_mode' => true,
        ];
    }

    public static function load($path = null)
    {
        return new static($path);
    }

    public function __construct($path = null)
    {
        $this->data = [];
        if (is_file($path)) {
            // Get file information
            $info = pathinfo($path);
            $parts = explode('.', $info['basename']);
            $extension = array_pop($parts);
            $parser = $this->getParser($extension);
            // Try and load file
            $this->data = array_replace_recursive($this->data, (array)$parser->parse($path));
        }
        parent::__construct($this->data);
    }

    private function getParser($extension)
    {
        $parser = null;
        foreach ($this->supportedFileParsers as $fileParser) {
            $tempParser = new  $fileParser;
            if (in_array($extension, $tempParser->getSupportedExtensions($extension))) {
                $parser = $tempParser;
                continue;
            }
        }
        // If none exist, then throw an exception
        if ($parser === null) {
            throw new Exception('Unsupported configuration format');
        }

        return $parser;
    }

    
}