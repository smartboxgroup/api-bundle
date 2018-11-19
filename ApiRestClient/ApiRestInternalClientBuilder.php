<?php

namespace Smartbox\ApiRestClient;

/**
 * Class ApiRestInternalClientBuilder.
 */
class ApiRestInternalClientBuilder
{
    /**
     * Static method to create a APIRestInternalClient.
     *
     * @param null $class
     * @param $baseUrl
     * @param $username
     * @param $password
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function createClientWithUrl($class = null, $baseUrl, $username, $password)
    {
        if (!empty($class)) {
            if (!\class_exists($class)) {
                throw new \LogicException("$class does not exists");
            } elseif (!\is_subclass_of($class, ApiRestInternalClient::$class, true)) {
                throw new \LogicException("$class is not an instance of ApiRestInternalClient");
            }
        } else {
            $class = ApiRestInternalClient::$class;
        }

        $client = new $class($username, $password, $baseUrl);

        return $client;
    }

    /**
     * Static method to create a APIRestInternalClient.
     *
     * @param null $class
     * @param $env
     * @param $username
     * @param $password
     * @param bool $mocks
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function createClient($class = null, $env, $username, $password, $mocks = false)
    {
        if ($mocks) {
            $baseUrl = MocksEnvironments::getEnvironmentURI($env);
        } else {
            $baseUrl = Environments::getEnvironmentURI($env);
        }

        return ApiRestInternalClientBuilder::createClientWithUrl($class, $baseUrl, $username, $password);
    }
}
