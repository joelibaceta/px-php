<?php
namespace MercadoPago;

use Exception;

/**
 * MercadoPago cURL RestClient
 */
class RestClient
{

    const VERB_ARRAY = [
        'get'    => 'GET',
        'post'   => 'POST',
        'put'    => 'PUT',
        'delete' => 'DELETE'
    ];

    protected $_httpRequest = null;
    protected static $_defaultParams = [];

    public function __construct()
    {
        $this->_httpRequest = new Http\CurlRequest();
    }

    protected static function set_headers(Http\HttpRequest $connect, $headers)
    {
        $default_header = ['Content-Type' => 'application/json'];
        if ($headers) {
            $default_header = array_merge($default_header, $headers);
        }

        $connect->setOption(CURLOPT_HTTPHEADER, $default_header);
    }

    protected static function set_data(Http\HttpRequest $connect, $data, $content_type = '')
    {
        if ($content_type == "application/json") {
            if (gettype($data) == "string") {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if (function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error != JSON_ERROR_NONE) {
                    throw new Exception("JSON Error [{$json_error}] - Data: {$data}");
                }
            }
        }

        $connect->setOption(CURLOPT_POSTFIELDS, $data);
    }

    public function setHttpRequest($request) {
        $this->_httpRequest = $request;
    }

    public function getHttpRequest() {
        return $this->_httpRequest;
    }

    protected function exec($options)
    {
        $method = key($options);
        $requestPath = reset($options);
        $verb = self::VERB_ARRAY[$method];

        $headers = self::getArrayValue($options, 'headers');
        $url_query = self::getArrayValue($options, 'url_query');
        $formData = self::getArrayValue($options, 'form_data');
        $jsonData = self::getArrayValue($options, 'json_data');
        $defaultHttpParams = self::$_defaultParams;

        $connectionParams = $defaultHttpParams;
        $query = '';
        if ($url_query > 0) {
            $query = http_build_query($url_query);
        }
        $address = self::getArrayValue($connectionParams, 'address');
        $uri = $address . $requestPath;
        if ($query != '') {
            $uri .= '?' . $query;
        }

        $connect = $this->getHttpRequest();
        $connect->setOption(CURLOPT_URL, $uri);

        //curl_setopt($connect, CURLOPT_USERAGENT, "MercadoPago Magento-1.9.x-transparent Cart v1.0.2");
        $connect->setOption(CURLOPT_RETURNTRANSFER, true);
        $connect->setOption(CURLOPT_CUSTOMREQUEST, $verb);

        self::set_headers($connect, $headers);

        $proxyAddress = self::getArrayValue($connectionParams, 'proxy_addr');
        $proxyPort = self::getArrayValue($connectionParams, 'proxy_port');
        if (!empty($proxyAddress)) {
            $connect->setOption(CURLOPT_PROXY, $proxyAddress);
            $connect->setOption(CURLOPT_PROXYPORT, $proxyPort);
        }
        if ($useSsl = self::getArrayValue($connectionParams, 'use_ssl')) {
            $connect->setOption(CURLOPT_SSL_VERIFYPEER, $useSsl);
        }
        if ($sslVersion = self::getArrayValue($connectionParams, 'ssl_version')) {
            $connect->setOption(CURLOPT_SSLVERSION, $sslVersion);
        }
        if ($verifyMode = self::getArrayValue($connectionParams, 'verify_mode')) {
            $connect->setOption(CURLOPT_SSL_VERIFYHOST, $verifyMode);
        }
        if ($caFile = self::getArrayValue($connectionParams, 'ca_file')) {
            $connect->setOption(CURLOPT_CAPATH, $caFile);
        }

        if ($formData) {
            self::set_data($connect, $formData);
        }
        if ($jsonData) {
            self::set_data($connect, $jsonData, "application/json");
        }

        $apiResult = $connect->execute();
        $apiHttpCode = $connect->getInfo(CURLINFO_HTTP_CODE);
        if ($apiResult === false) {
            throw new Exception ($connect->error());
        }
        $response['response'] = [];
        if ($apiHttpCode == "200" || $apiHttpCode == "201") {
            $response['response'] = json_decode($apiResult, true);
        }

        $response['code'] = $apiHttpCode;

        $connect->error();

        return ['code' => $response['code'], 'body' => $response['response']];
    }

    public function get($uri, $options = [])
    {
        return $this->exec(array_merge(['get' => $uri], $options));
    }

    public function post($uri, $options = [])
    {
        return $this->exec(array_merge(['post' => $uri], $options));
    }

    public function put($uri, $options = [])
    {
        return $this->exec(array_merge(['put' => $uri], $options));
    }

    public function delete($uri, $options = [])
    {
        return $this->exec(array_merge(['delete' => $uri], $options));
    }

    public function setHttpParam($param, $value)
    {
        self::$_defaultParams[$param] = $value;
    }

    private function getArrayValue($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return false;
        }
    }
}
