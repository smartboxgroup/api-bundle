<?php
namespace Smartbox\ApiRestClient;

/**
 * Class Environments
 *
 * @package Smartbox\ApiRestClient
 */
class Environments
{
    const ENV_TEST          = "test";
    const ENV_DEMO          = "demo";
    const ENV_SANDBOX       = "sandbox";
    const ENV_INT_APOLLO    = "int-apollo";
    const ENV_STAGING       = "staging";
    const ENV_PREPROD       = "preprod";
    const ENV_PROD          = "prod";

    protected static $environments = array(
        self::ENV_TEST          => "http://real.smartesb.local",
        self::ENV_DEMO          => "http://eai-demo.sandbox.local",
        self::ENV_SANDBOX       => "http://eai.sandbox.local",
        self::ENV_INT_APOLLO    => "http://eai-tt-one17.smartbox-test.local",
        self::ENV_STAGING       => "http://eai-pp-one17.production.smartbox.com",
        self::ENV_PREPROD       => "http://eai-pp.production.smartbox.com",
        self::ENV_PROD          => "http://eai.production.smartbox.com"
    );

    /**
     * Return the entry point of the given environment
     *
     * @param $env
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getEnvironmentURI($env)
    {
        if (!isset(self::$environments[$env])){
            throw new \Exception("Unknown environment $env");
        }
        return self::$environments[$env];
    }
}