<?php

namespace Smartbox\ApiRestClient;

use JMS\Serializer\SerializerBuilder;
use Psr\Http\Message\ResponseInterface;

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
     * @param ResponseInterface $guzzleResponse
     * @param string $deserializationType
     *
     * @return ApiRestResponse
     */
    public static function buildResponse(ResponseInterface $guzzleResponse, $deserializationType)
    {
        $apiRestResponse = new ApiRestResponse();

        $content = (string) $guzzleResponse->getBody();
        if (!empty($content)){
            if (!empty($serializationType))  {

                $serializer = SerializerBuilder::create()->build();
                $jsonContent = $serializer->deserialize($content, $deserializationType, ApiRestInternalClient::FORMAT_JSON);

                $apiRestResponse->setBody($jsonContent);
            }else{
                $apiRestResponse->setBody($content);
            }
        }

        //Flatten headers array
        $headers = [];
        foreach ($guzzleResponse->getHeaders() as $name=>$value){
            $headers[$name] = $guzzleResponse->getHeaderLine($name);
        }
        $apiRestResponse->setHeaders($headers);

        $apiRestResponse->setTransactionId($guzzleResponse->getHeaderLine(ApiRestResponse::TRANSACTION_ID));
        $apiRestResponse->setRateLimitLimit($guzzleResponse->getHeaderLine(ApiRestResponse::RATE_LIMIT_LIMIT));
        $apiRestResponse->setRateLimitRemaining($guzzleResponse->getHeaderLine(ApiRestResponse::RATE_LIMIT_REMAINING));
        $apiRestResponse->setRateLimitReset($guzzleResponse->getHeaderLine(ApiRestResponse::RATE_LIMIT_RESET));
        $apiRestResponse->setRateLimitResetRemaining($guzzleResponse->getHeaderLine(ApiRestResponse::RATE_LIMIT_RESET_REMAINING));

        $apiRestResponse->setStatusCode($guzzleResponse->getStatusCode());

        return $apiRestResponse;
    }
}