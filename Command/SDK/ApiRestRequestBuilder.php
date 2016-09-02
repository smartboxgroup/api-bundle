<?php

namespace Smartbox\ApiRestClient;

use JMS\Serializer\SerializerBuilder;

class ApiRestRequestBuilder
{

    /**
     * @param $username
     * @param $password
     * @param null $object
     * @param array $headers
     * @param array $filters
     *
     * @return array
     */
    public static function buildRequest($username = null, $password = null, $object = null, $headers = [], $filters = [])
    {
        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($object, ApiRestInternalClient::FORMAT_JSON);

        $request = array_merge(['body' => $jsonContent], self::getOptions($username, $password));

        if (!empty($filters)) {
            $request["query"] = $filters;
        }

        if (!empty($headers)) {
            $request["headers"] = array_merge($request["headers"], $headers);
        }

        return $request;
    }

    /**
     * @param $username
     * @param $password
     *
     * @return array
     */
    protected static function getOptions($username, $password)
    {
        return [
            'auth' => [
                $username,
                $password
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ];
    }
}