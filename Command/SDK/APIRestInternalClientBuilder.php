<?php
namespace Smartbox\ApiRestClient;

/**
 * Class ApiRestInternalClientBuilder
 *
 * @package Smartbox\ApiRestClient
 */
class ApiRestInternalClientBuilder
{
    /**
     * Static method to create a APIRestInternalClient.
     *
     * @param $env
     * @param $username
     * @param $password
     * @param null $class
     *
     * @return mixed
     * @throws \Exception
     */
    public static function createClient($class = null, $env, $username, $password)
    {
        if(!empty($class)){
            if(!class_exists($class)){
                throw new \LogicException("$class does not exists");
            }elseif (!is_subclass_of($class, ApiRestInternalClient::$class, true) ){
                throw new \LogicException("$class is not a subclass of ApiRestInternalClient");
            }
        }else{
            $class = ApiRestInternalClient::$class;
        }

        $baseUrl = Environments::getEnvironmentURI($env);

        $client = new $class($username, $password, $baseUrl);

        return $client;
    }
}
