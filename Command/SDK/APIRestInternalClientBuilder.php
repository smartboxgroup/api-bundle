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
     * @param $env
     * @param $username
     * @param $password
     * @param null $class
     *
     * @return mixed
     * @throws \Exception
     */
    public static function createClient($env, $username, $password, $class = null)
    {
        if(!empty($class)){
            if(!class_exists($class)){
                throw new \Exception("$class does not exists");
            }elseif (!is_subclass_of($class, ApiRestInternalClient::class, true) ){
                throw new \Exception("$class is not an instance of ApiRestInternalClient");
            }
        }else{
            $class = ApiRestInternalClient::class;
        }

        $baseUrl = (new Environments())->getEnvironmentURI($env);

        $client = new $class($username, $password, $baseUrl);

        return $client;
    }
}
