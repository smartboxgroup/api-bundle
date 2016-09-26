<?php

namespace Smartbox\ApiRestClient;

use Guzzle\Http\Message\Response;
use JMS\Serializer\SerializerBuilder;

/**
 * Class ApiRestResponseBuilder
 *
 * @package Smartbox\ApiRestClient
 */
class ApiRestResponseBuilder
{

    /**
     * Build the ApiRestResponse from the Guzzle response
     *
     * @param Response $guzzleResponse
     * @param string $deserializationType
     *
     * @return ApiRestResponse
     */
    public static function buildResponse(Response $guzzleResponse, $deserializationType = null)
    {
        $apiRestResponse = new ApiRestResponse();

        $content = (string) $guzzleResponse->getBody();
        if (!empty($content)){
            if (!empty($deserializationType))  {

                $serializer = SerializerBuilder::create()->build();
                $jsonContent = $serializer->deserialize($content, $deserializationType, ApiRestInternalClient::FORMAT_JSON);

                $apiRestResponse->setBody($jsonContent);
            }else{
                $apiRestResponse->setBody($content);
            }
            $apiRestResponse->setRawBody($content);
        }
        //Flatten headers array
        $headers = array();
        foreach ($guzzleResponse->getHeaders() as $name=>$value){
            $headers[$name] = $guzzleResponse->getHeader($name)->__toString();
        }
        $apiRestResponse->setHeaders($headers);

        if(isset($headers[ApiRestResponse::RATE_LIMIT_LIMIT])){
            $apiRestResponse->setRateLimitLimit($headers[ApiRestResponse::RATE_LIMIT_LIMIT]);
        }
        if(isset($headers[ApiRestResponse::RATE_LIMIT_REMAINING])){
            $apiRestResponse->setRateLimitRemaining($headers[ApiRestResponse::RATE_LIMIT_REMAINING]);
        }
        if(isset($headers[ApiRestResponse::RATE_LIMIT_RESET_REMAINING])){
            $apiRestResponse->setRateLimitResetRemaining($headers[ApiRestResponse::RATE_LIMIT_RESET_REMAINING]);
        }
        if(isset($headers[ApiRestResponse::RATE_LIMIT_RESET])){
            $apiRestResponse->setRateLimitReset($headers[ApiRestResponse::RATE_LIMIT_RESET]);
        }

        $apiRestResponse->setStatusCode($guzzleResponse->getStatusCode());

        return $apiRestResponse;
    }
}