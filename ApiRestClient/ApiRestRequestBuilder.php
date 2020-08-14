<?php

namespace Smartbox\ApiRestClient;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

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
     * @return \GuzzleHttp\Psr7\Request|mixed
     */
    public static function buildRequest($method, $url, $username = null, $password = null, $object = null, $headers = [], $filters = [])
    {
        $jsonContent = null;
        if (!empty($object)) {
            $serializer = JMSSerializerBuilder::buildSerializer();
            $jsonContent = $serializer->serialize($object, ApiRestInternalClient::FORMAT_JSON);
        }

        $headers = \array_merge($headers, self::getOptions($username, $password));
        $uri = UriResolver::resolve(new Uri($url), new Uri(''));
        foreach ($filters as  $key => $value) {
            if (\is_bool($value)) {
                $uri = Uri::withQueryValue($uri, $key, $value ? 'true' : 'false');
            } else {
                $uri = Uri::withQueryValue($uri, $key, $value);
            }
        }
        $request = new Request($method, $uri, $headers, $jsonContent);

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
        return [
            'Authorization' => 'Basic '.\base64_encode($username.':'.$password),
            'Content-Type' => 'application/json',
        ];
    }
}
