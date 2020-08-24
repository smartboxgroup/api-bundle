<?php

namespace Smartbox\ApiRestClient;

/**
 * Class ApiRestException.
 */
class ApiRestException extends \Exception
{
    /**
     * @var ApiRestResponse
     */
    public $apiRestResponse;

    /**
     * ApiRestException constructor.
     */
    public function __construct(ApiRestResponse $apiRestResponse, \Exception $previous = null)
    {
        parent::__construct($apiRestResponse->getRawBody(), $apiRestResponse->getStatusCode(), $previous);
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
