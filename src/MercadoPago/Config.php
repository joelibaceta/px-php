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

//    public function getToken()
//    {
//
//        $data = ['grant_type'    => 'client_credentials',
//                 'client_id'     => '446950613712741',
//                 'client_secret' => '0WX05P8jtYqCtiQs6TH1d9SyOJ04nhEv'];
//        RestClient::setHttpParam('address', $this->get('base_url'));
//        //RestClient::setHttpParam('use_ssl', true);
//        //RestClient::setHttpParam('ca_file', dirname(__FILE__) . '/mercadopago/ca-bundle.crt');
//        //$response = RestClient::post("/oauth/token", ['json_data' => json_encode($data)]);
//        $response = RestClient::get("/item_categories");
//        return $response['body'];
//    }


}