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
     * @param boolean $mocks
     *
     * @return mixed
     * @throws \Exception
     */
    public static function createClient($class = null, $env, $username, $password, $mocks = false)
    {
        if(!empty($class)){
            if(!class_exists($class)){
                throw new \LogicException("$class does not exists");
            }elseif (!is_subclass_of($class, ApiRestInternalClient::$class, true) ){
                throw new \LogicException("$class is not an instance of ApiRestInternalClient");
            }
        }else{
            $class = ApiRestInternalClient::$class;
        }

        if($mocks){
            $baseUrl = MocksEnvironments::getEnvironmentURI($env);
        }else{
            $baseUrl = Environments::getEnvironmentURI($env);
        }

        $client = new $class($username, $password, $baseUrl);

        return $client;
    }
}
