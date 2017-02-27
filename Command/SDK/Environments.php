<?php
namespace Smartbox\ApiRestClient;

/**
 *  AUTO-GENERATED
 *  Place here your environment entry point URIs
 */
class Environments
{
    public static $environments = [
        'test' => "www.example.com/api/test/example",       // Replace this with real data
        'production' => "www.example.com/api/example"       // Replace this with real data
    ];

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