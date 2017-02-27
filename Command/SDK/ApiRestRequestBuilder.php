<?php

namespace Smartbox\ApiRestClient;

use Guzzle\Http\Message\RequestFactory;

/**
 * Class ApiRestRequestBuilder.
 */
class ApiRestRequestBuilder
{
    public static $class = 'Smartbox\ApiRestClient\ApiRestRequestBuilder';

    /**
     * Build a Guzzle request.
     *
     * @param $method
     * @param $url
     * @param null  $username
     * @param null  $password
     * @param null  $object
     * @param array $headers
     * @param array $filters
     *
     * @return \Guzzle\Http\Message\RequestInterface|mixed
     */
    public static function buildRequest($method, $url, $username = null, $password = null, $object = null, $headers = array(), $filters = array())
    {
        $jsonContent = null;
        if (!empty($object)) {
            $serializer = JMSSerializerBuilder::buildSerializer();
            $jsonContent = $serializer->serialize($object, ApiRestInternalClient::FORMAT_JSON);
        }

        $headers = array_merge($headers, self::getOptions($username, $password));

        $factory = new RequestFactory();

        $request = $factory->create($method, $url, $headers, $jsonContent, self::getOptions($username, $password));

        $query = $request->getQuery();

        foreach ($filters as  $key => $value) {
            if (is_bool($value)) {
                $value = ($value) ? 'true' : 'false';
            }
            $query->add($key, $value);
        }

        return $request;
    }

    /**
     * Return an array of options.
     *
     * @param $username
     * @param $password
     *
     * @return array
     */
    protected static function getOptions($username, $password)
    {
        return array(
            'auth' => array(
                $username,
                $password,
            ),
            'Content-Type' => 'application/json',
        );
    }
}
