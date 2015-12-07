<?php

namespace Smartbox\ApiBundle\Services\Rest;

use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesLoader extends Loader
{

    /** @var  ApiConfigurator */
    protected $apiConfigurator;

    function __construct($apiConfigurator)
    {
        $this->apiConfigurator = $apiConfigurator;
    }

    /**
     * @return ApiConfigurator
     */
    public function getApiConfigurator()
    {
        return $this->apiConfigurator;
    }

    /**
     * @param ApiConfigurator $apiConfigurator
     */
    public function setApiConfigurator(ApiConfigurator $apiConfigurator)
    {
        $this->apiConfigurator = $apiConfigurator;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return RouteCollection
     * @throws \Exception If something went wrong
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        $serviceConfig = $this->apiConfigurator->getConfig($resource);

        $version = $serviceConfig['version'];
        $name = $serviceConfig['name'];

        $restBasePath = '/rest/'.$name.'/'.$version;

        // Add the rest routes
        foreach ($serviceConfig['methods'] as $method => $methodConfig) {
            $controller = $methodConfig['controller'];
            $route_path = $methodConfig['rest']['route'];
            $requirements = array();

            foreach ($methodConfig[ApiConfigurator::INPUT] as $input => $inputConfig) {
                if ($inputConfig['mode'] == Configuration::MODE_REQUIREMENT && array_key_exists(
                        'format',
                        $inputConfig
                    )
                ) {
                    $requirements[$input] = $inputConfig['format'];
                }
            }

            $route_method = $methodConfig['rest']['httpMethod'];
            $defaults = array(
                '_controller' => $controller,
                '_generated' => 'smartapi',
                'api' => 'rest',
                ApiConfigurator::SERVICE_ID => $resource,
                ApiConfigurator::VERSION => $version,
                ApiConfigurator::SERVICE_NAME => $name,
                ApiConfigurator::METHOD_NAME => $method,
                ApiConfigurator::METHOD_CONFIG => $methodConfig
            );

            $defaults = array_merge($defaults, $methodConfig['defaults']);

            $routeRest = new Route(
                $restBasePath.$route_path,
                $defaults,
                $requirements,
                array(),
                '',
                array(),
                array($route_method)
            );
            $routeName = $this->apiConfigurator->getRestRouteNameFor($resource, $method);
            $routes->add($routeName, $routeRest);
        }

        return $routes;
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && $this->apiConfigurator->hasService($resource) && 'smartapi_rest' === $type;
    }
}