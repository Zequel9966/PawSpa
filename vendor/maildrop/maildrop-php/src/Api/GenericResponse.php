<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Psr\Http\Message\ResponseInterface;
use Maildrop\Exception\ResponseException;

class GenericResponse
{
    protected $response;

    protected $json_data;

    protected $json_is_decoded = false;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    protected function decode_json()
    {
        $contentType = $this->response->getHeaderLine('Content-Type');

        if (0 !== \strpos($contentType, 'application/json')) {
            throw new ResponseException('Cannot instanciate response with Content-Type: '.$contentType);
        }

        $body = $this->response->getBody()->__toString();
        $this->json_data = json_decode($body);

        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw new ResponseException(sprintf('Error (%d) when trying to json_decode response', \json_last_error()));
        }

        $this->json_is_decoded = true;
    }

    public function success()
    {
        $this->decode_json();
        return (floor($this->response->getStatusCode() / 100) == 2);
    }

    public function getErrorCode()
    {
        $this->decode_json();
        return $this->json_data->code;
    }

    public function getErrorMsg()
    {
        $this->decode_json();
        return \trim(\explode(":", $this->json_data->message, 2)[1]);
    }

    public function getRawData()
    {
        $this->decode_json();
        return $this->json_data;
    }

    public function getRaw() {
        return $this->response->getBody()->__toString();
    }

    public function getData()
    {
        $this->decode_json();
        return $this->json_data->data->data??$this->json_data->data??[];
    }
}
