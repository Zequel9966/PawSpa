<?php

/**
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Maildrop;

use GuzzleHttp\Client;

class Maildrop
{
    static private $default_client_api_key;
    static private $default_partner_api_key;

    private $client_api_key;
    private $partner_api_key;

    private $httpClient=null;
    static private $endpoint = 'https://api.dpmail.fr/json/';

    private $http_default_options = [
        'connect_timeout' => 5,
        'timeout' => 25
    ];

    private $http_options = [];

    public function __construct($param_http_options=null)
    {
        if (\is_array($param_http_options)) {
            $validated_user_http_options = \array_intersect_key($param_http_options, $this->http_default_options);
            $this->http_options = \array_merge($this->http_default_options, $validated_user_http_options);
        }

    }

    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client(\array_merge($this->http_options, [
                    'base_uri' => self::$endpoint,
                    'http_errors' => false,
                    'headers' => [
                        'User-Agent' => 'maildrop-php/1.0',
                    ]
                ]));
        }

        return $this->httpClient;
    }

    public static function setDefaultClientApiKey($client_api_key)
    {
        self::$default_client_api_key = $client_api_key;
    }

    public function setClientApiKey($client_api_key)
    {
        $this->client_api_key = $client_api_key;
    }

    public static function setDefaultPartnerApiKey($partner_api_key)
    {
        self::$default_partner_api_key = $partner_api_key;
    }

    public function setPartnerApiKey($partner_api_key)
    {
        $this->partner_api_key = $partner_api_key;
    }

    public static function setEndpoint($endpoint)
    {
        self::$endpoint = $endpoint;
    }

    public function getClientApiKey()
    {
        if (! is_null($this->client_api_key)) {
            return $this->client_api_key;
        }

        return self::$default_client_api_key;
    }

    public function getPartnerApiKey()
    {
        if (! is_null($this->partner_api_key)) {
            return $this->partner_api_key;
        }

        return self::$default_partner_api_key;
    }

    /**
     * @return Api\Tests
     */
    public function tests()
    {
        return new Api\Tests($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Lists
     */
    public function lists()
    {
        return new Api\Lists($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\CustomFields
     */
    public function custom_fields()
    {
        return new Api\CustomFields($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Segments
     */
    public function segments()
    {
        return new Api\Segments($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Subscribers
     */
    public function subscribers()
    {
        return new Api\Subscribers($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Reports
     */
    public function reports()
    {
        return new Api\Reports($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Campaigns
     */
    public function campaigns()
    {
        return new Api\Campaigns($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\IpPools
     */
    public function ip_pools()
    {
        return new Api\IpPools($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\SendingDomains
     */
    public function sending_domains()
    {
        return new Api\SendingDomains($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\TrackingDomains
     */
    public function tracking_domains()
    {
        return new Api\TrackingDomains($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Transactionals
     */
    public function transactionals()
    {
        return new Api\Transactionals($this->getClientApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Partner
     */
    public function partner()
    {
        return new Api\Partner($this->getPartnerApiKey(), $this->getHttpClient());
    }

    /**
     * @return Api\Clients
     */
    public function clients()
    {
        return new Api\Clients($this->getPartnerApiKey(), $this->getHttpClient());
    }
}
