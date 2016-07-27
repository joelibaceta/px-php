<?php
namespace MercadoPago;

use Exception;
use MercadoPago\Config\Json;
use MercadoPago\Config\Yaml;

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
                'sandbox_mode'  => true,
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

    public function set($key, $value)
    {
        parent::set($key, $value);
        if ($this->get('CLIENT_ID') != "" && $this->get('CLIENT_SECRET') != "") {
            $response = $this->getToken();
            if (isset($response['access_token']) && isset($response['refresh_token'])) {
                parent::set('ACCESS_TOKEN', $response['access_token']);
                parent::set('REFRESH_TOKEN', $response['refresh_token']);
            }
        }
    }

    public function getToken()
    {
        $restClient = new RestClient();
        $data = ['grant_type'    => 'client_credentials',
                 'client_id'     => $this->get('CLIENT_ID'),
                 'client_secret' => $this->get('CLIENT_SECRET')];
        $restClient->setHttpParam('address', $this->get('base_url'));
        $restClient->setHttpParam('use_ssl', true);
        $response = $restClient->post("/oauth/token", ['json_data' => json_encode($data)]);
        return $response['body'];
    }

}