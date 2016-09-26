<?php

namespace Smartbox\ApiRestClient;

/**
 * Class ApiRestException
 *
 * @package Smartbox\ApiRestClient
 */
class ApiRestException extends \Exception
{
    /**
     * @var ApiRestResponse
     */
    public $apiRestResponse;

    /**
     * ApiRestException constructor.
     *
     * @param ApiRestResponse $apiRestResponse
     */
    public function __construct(ApiRestResponse $apiRestResponse)
    {
        parent::__construct($apiRestResponse->getRawBody());
        $this->apiRestResponse = $apiRestResponse;
    }

    /**
     * @return ApiRestResponse
     */
    public function getApiRestResponse()
    {
        return $this->apiRestResponse;
    }

    /**
     * @param ApiRestResponse $apiRestResponse
     */
    public function setApiRestResponse($apiRestResponse)
    {
        $this->apiRestResponse = $apiRestResponse;
    }
}