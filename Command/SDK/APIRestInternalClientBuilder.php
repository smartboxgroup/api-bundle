<?php

/**
 * Class APIRestInternalClientBuilder.
 */
class APIRestInternalClientBuilder
{
    const ENV_DEV = 'dev';
    const ENV_DEMO = 'demo';
    const ENV_SANDBOX = 'sandbox';
    const ENV_PREPROD = 'preprod';
    const ENV_PROD = 'prod';

    const BASE_URL_DEV      = "http://real.smartesb.local";
    const BASE_URL_DEMO     = "";
    const BASE_URL_SANDBOX  = "";
    const BASE_URL_PREPROD  = "";
    const BASE_URL_PROD     = "";

    /**
     * Static method to create a BifrostSDK.
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
            }elseif ((new \ReflectionClass($class)) instanceof APIRestInternalClient){
                throw new \Exception("$class is not an instance of APIRestInternalClient");
            }
        }else{
            $class = APIRestInternalClient::class;
        }

        switch ($env) {
            case self::ENV_DEV:
                $basUrl = self::BASE_URL_DEV;
                break;
            case self::ENV_DEMO:
                $basUrl = 'http://real.smartesb.local';
                break;
            case self::ENV_SANDBOX:
                $basUrl = 'http://real.smartesb.local';
                break;
            case self::ENV_PREPROD:
                $basUrl = 'http://real.smartesb.local';
                break;
            case self::ENV_PROD:
                $basUrl = 'http://real.smartesb.local';
                break;
            default:
                throw new \Exception("Unknown environment $env when trying to built the BifrostClient");
        }

        $client = new $class($username, $password, $basUrl);

        return $client;
    }
}
