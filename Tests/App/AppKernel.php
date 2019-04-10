<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    private static $cacheDir;

    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new BeSimple\SoapBundle\BeSimpleSoapBundle(),
            new Noxlogic\RateLimitBundle\NoxlogicRateLimitBundle(),
            new Snc\RedisBundle\SncRedisBundle(),

            new Smartbox\CoreBundle\SmartboxCoreBundle(),
            new Smartbox\ApiBundle\SmartboxApiBundle(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }

    public function getCacheDir()
    {
        if (!static::$cacheDir) {
            static::$cacheDir = sys_get_temp_dir().'/api_bundle_test_'.md5(random_bytes(10));
        }

        return static::$cacheDir;
    }
}
