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

    private static $_defaultParams = [];

    const API_BASE_URL = "https://api.mercadopago.com";

    private static function get_connect($uri, $method)
    {
        if (!extension_loaded("curl")) {
            throw new Exception("cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.");
        }

        $connect = curl_init($uri);

        //curl_setopt($connect, CURLOPT_USERAGENT, "MercadoPago Magento-1.9.x-transparent Cart v1.0.2");
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);


        return $connect;
    }

    private static function set_headers(&$connect, $headers)
    {
        $default_header = ['Content-Type' => 'application/json'];
        if (count($headers) > 0) {
            $default_header = array_merge($default_header, $headers);
        }

        curl_setopt($connect, CURLOPT_HTTPHEADER, $default_header);
    }

    private static function set_data(&$connect, $data, $content_type = '')
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

        curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
    }

    private function exec($options)
    {
        $method = key($options);
        $requestPath = reset($options);
        $verb = self::VERB_ARRAY[$method];

        $headers = self::getArrayValue($options, 'headers');
        $url_query = self::getArrayValue($options, 'url_query');
        $form_data = self::getArrayValue($options, 'form_data');
        $json_data = self::getArrayValue($options, 'json_data');
        $default_http_params = self::$_defaultParams;

        $connection_params = $default_http_params;
        $query = '';
        if (count($url_query) > 0) {
            $query = http_build_query($url_query);
        }
        $address = self::getArrayValue($connection_params, 'address');
        $uri = $address . $requestPath;
        if ($query != '') {
            $uri .= '?' . $query;
        }

        $connect = self::get_connect($uri, $verb);
//        curl_setopt($connect, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($connect, CURLOPT_VERBOSE, 1);
//        curl_setopt($connect, CURLOPT_HEADER, 1);
        self::set_headers($connect, $headers);

        $proxy_addr = self::getArrayValue($connection_params, 'proxy_addr');
        $proxy_port = self::getArrayValue($connection_params, 'proxy_port');
        if (!empty($proxy_addr)) {
            curl_setopt($connect, CURLOPT_PROXY, $proxy_addr);
            curl_setopt($connect, CURLOPT_PROXYPORT, $proxy_port);
        }
        if (self::getArrayValue($connection_params, 'use_ssl')) {
            curl_setopt($connect, CURLOPT_SSL_VERIFYPEER, self::getArrayValue($connection_params, 'use_ssl'));
        }
        if (self::getArrayValue($connection_params, 'ssl_version')) {
            curl_setopt($connect, CURLOPT_SSLVERSION, self::getArrayValue($connection_params, 'ssl_version'));
        }
        if (self::getArrayValue($connection_params, 'verify_mode')) {
            curl_setopt($connect, CURLOPT_SSL_VERIFYHOST, self::getArrayValue($connection_params, 'verify_mode'));
        }
        if (self::getArrayValue($connection_params, 'ca_file')) {
            curl_setopt($connect, CURLOPT_CAPATH, self::getArrayValue($connection_params, 'ca_file'));
        }

        if ($form_data) {
            self::set_data($connect, $form_data);
        }
        if ($json_data) {
            self::set_data($connect, $json_data, "application/json");
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);
        if ($api_result === false) {
            throw new Exception (curl_error($connect));
        }
        $response['response'] = [];
        if ($api_http_code == "200" || $api_http_code == "201") {
            $response['response'] = json_decode($api_result, true);
        }

        $response['code'] = $api_http_code;
        /*if ($response['status'] >= 400) {
            $message = $response['response']['message'];
            if (isset ($response['response']['cause'])) {
                if (isset ($response['response']['cause']['code']) && isset ($response['response']['cause']['description'])) {
                    $message .= " - ".$response['response']['cause']['code'].': '.$response['response']['cause']['description'];
                } else if (is_array ($response['response']['cause'])) {
                    foreach ($response['response']['cause'] as $cause) {
                        $message .= " - ".$cause['code'].': '.$cause['description'];
                    }
                }
            }

            throw new Exception ($message, $response['status']);
        }*/

        curl_close($connect);

        return ['code' => $response['code'], 'body' => $response['response']];
    }

    public static function get($uri, $options = [])
    {
        return self::exec(array_merge(['get' => $uri], $options));
    }

    public static function post($uri, $options = [])
    {
        return self::exec(array_merge(['post' => $uri], $options));
    }

    public static function put($uri, $options = [])
    {
        return self::exec(array_merge(['put' => $uri], $options));
    }

    public static function delete($uri, $options = [])
    {
        return self::exec(array_merge(['delete' => $uri], $options));
    }

    public static function setHttpParam($param, $value)
    {
        self::$_defaultParams[$param] = $value;
    }

    private static function getArrayValue($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return null;
        }
    }
}
