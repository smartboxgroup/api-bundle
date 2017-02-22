<?php
namespace Smartbox\ApiRestClient;

/**
 * Class MocksEnvironments
 *
 * @package Smartbox\ApiRestClient
 */
class MocksEnvironments extends Environments
{
    const ENV_TEST      = "test";
    const ENV_DEMO      = "demo";
    const ENV_SANDBOX   = "sandbox";
    const ENV_PREPROD   = "preprod";
    const ENV_PROD      = "prod";

    protected static $environments = array(
        self::ENV_TEST      => "http://mocks.eai",
        self::ENV_DEMO      => "http://mocks.eai-demo.sandbox.local",
        self::ENV_SANDBOX   => "http://mocks.eai.sandbox.local",
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