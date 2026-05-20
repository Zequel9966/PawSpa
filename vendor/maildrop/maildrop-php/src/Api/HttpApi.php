<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Exception\ConnectException;

abstract class HttpApi
{
    protected $httpClient;
    protected $api_key;
    protected $callables;

    public function __construct($api_key, $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->api_key = $api_key;
    }

    public function __call($method, $arguments)
    {
        if (!isset($this->callables[$method])) {
            throw new \Exception("Undefined method \"{$method}\"");
        }

        $callable = $this->callables[$method];

        return call_user_func_array([$this, $callable[0]], [$callable[1], $arguments[0]??[], $callable[2], $callable[3]]);
    }

    protected function httpGet($path, $query_params, $parameters_formatter_class, $response_class)
    {
        try {
            $response =  $this->httpClient->request("GET", "{$path}.php", [
                    'auth' => [$this->api_key, ''],
                    'query' => $parameters_formatter_class::format($query_params),
                ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectException($e);
        }

        return new $response_class($response);
    }

    protected function httpPost($path, $form_params, $parameters_formatter_class, $response_class)
    {
        try {
            $response =  $this->httpClient->request("POST", "{$path}.php", [
                    'auth' => [$this->api_key, ''],
                    'form_params' => $parameters_formatter_class::format($form_params),
                ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectException($e);
        }

        return new $response_class($response);
    }
}
