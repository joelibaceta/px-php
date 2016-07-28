<?php
namespace MercadoPago;

use Exception;

/**
 * MercadoPago cURL RestClient
 */
class RestClient
{

    /**
     *
     */
    const VERB_ARRAY = [
        'get'    => 'GET',
        'post'   => 'POST',
        'put'    => 'PUT',
        'delete' => 'DELETE'
    ];

    /**
     * @var Http\CurlRequest|null
     */
    protected $httpRequest = null;
    /**
     * @var array
     */
    protected static $defaultParams = [];

    /**
     * RestClient constructor.
     */
    public function __construct()
    {
        $this->httpRequest = new Http\CurlRequest();
    }

    /**
     * @param Http\HttpRequest $connect
     * @param                  $headers
     */
    protected function setHeaders(Http\HttpRequest $connect, $headers)
    {
        $default_header = ['Content-Type' => 'application/json'];
        if ($headers) {
            $default_header = array_merge($default_header, $headers);
        }

        $connect->setOption(CURLOPT_HTTPHEADER, $default_header);
    }

    /**
     * @param Http\HttpRequest $connect
     * @param                  $data
     * @param string           $content_type
     *
     * @throws Exception
     */
    protected function setData(Http\HttpRequest $connect, $data, $content_type = '')
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

    /**
     * @param $request
     */
    public function setHttpRequest($request) {
        $this->httpRequest = $request;
    }

    /**
     * @return Http\CurlRequest|null
     */
    public function getHttpRequest() {
        return $this->httpRequest;
    }

    /**
     * @param $options
     *
     * @return array
     * @throws Exception
     */
    protected function exec($options)
    {
        $method = key($options);
        $requestPath = reset($options);
        $verb = self::VERB_ARRAY[$method];

        $headers = self::_getArrayValue($options, 'headers');
        $url_query = self::_getArrayValue($options, 'url_query');
        $formData = self::_getArrayValue($options, 'form_data');
        $jsonData = self::_getArrayValue($options, 'json_data');
        $defaultHttpParams = self::$defaultParams;

        $connectionParams = $defaultHttpParams;
        $query = '';
        if ($url_query > 0) {
            $query = http_build_query($url_query);
        }
        $address = self::_getArrayValue($connectionParams, 'address');
        $uri = $address . $requestPath;
        if ($query != '') {
            $uri .= '?' . $query;
        }

        $connect = $this->getHttpRequest();
        $connect->setOption(CURLOPT_URL, $uri);

        //curl_setopt($connect, CURLOPT_USERAGENT, "MercadoPago Magento-1.9.x-transparent Cart v1.0.2");
        $connect->setOption(CURLOPT_RETURNTRANSFER, true);
        $connect->setOption(CURLOPT_CUSTOMREQUEST, $verb);

        self::setHeaders($connect, $headers);

        $proxyAddress = self::_getArrayValue($connectionParams, 'proxy_addr');
        $proxyPort = self::_getArrayValue($connectionParams, 'proxy_port');
        if (!empty($proxyAddress)) {
            $connect->setOption(CURLOPT_PROXY, $proxyAddress);
            $connect->setOption(CURLOPT_PROXYPORT, $proxyPort);
        }
        if ($useSsl = self::_getArrayValue($connectionParams, 'use_ssl')) {
            $connect->setOption(CURLOPT_SSL_VERIFYPEER, $useSsl);
        }
        if ($sslVersion = self::_getArrayValue($connectionParams, 'ssl_version')) {
            $connect->setOption(CURLOPT_SSLVERSION, $sslVersion);
        }
        if ($verifyMode = self::_getArrayValue($connectionParams, 'verify_mode')) {
            $connect->setOption(CURLOPT_SSL_VERIFYHOST, $verifyMode);
        }
        if ($caFile = self::_getArrayValue($connectionParams, 'ca_file')) {
            $connect->setOption(CURLOPT_CAPATH, $caFile);
        }

        if ($formData) {
            self::setData($connect, $formData);
        }
        if ($jsonData) {
            self::setData($connect, $jsonData, "application/json");
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

    /**
     * @param       $uri
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function get($uri, $options = [])
    {
        return $this->exec(array_merge(['get' => $uri], $options));
    }

    /**
     * @param       $uri
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function post($uri, $options = [])
    {
        return $this->exec(array_merge(['post' => $uri], $options));
    }

    /**
     * @param       $uri
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function put($uri, $options = [])
    {
        return $this->exec(array_merge(['put' => $uri], $options));
    }

    /**
     * @param       $uri
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function delete($uri, $options = [])
    {
        return $this->exec(array_merge(['delete' => $uri], $options));
    }

    /**
     * @param $param
     * @param $value
     */
    public function setHttpParam($param, $value)
    {
        self::$defaultParams[$param] = $value;
    }

    /**
     * @param $array
     * @param $key
     *
     * @return bool
     */
    private function _getArrayValue($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return false;
        }
    }
}
