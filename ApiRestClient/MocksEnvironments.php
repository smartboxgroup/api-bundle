<?php

namespace Smartbox\ApiRestClient;

/**
 * Class MocksEnvironments.
 */
class MocksEnvironments extends Environments
{
    //const ENV_TEST      = "test";

    protected static $environments = [
        //self::ENV_TEST      => "http://mocks.eai",
    ];

    /**
     * Return the entry point of the given environment.
     *
     * @param $env
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getEnvironmentURI($env)
    {
        if (!isset(self::$environments[$env])) {
            throw new \Exception("Unknown environment $env");
        }

        return self::$environments[$env];
    }
}
