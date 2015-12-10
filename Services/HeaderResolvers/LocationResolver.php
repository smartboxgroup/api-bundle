<?php

namespace Smartbox\ApiBundle\Services\HeaderResolvers;


use Smartbox\ApiBundle\Entity\Location;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

class LocationResolver
{

    /** @var  Router */
    protected $router;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  ApiConfigurator */
    protected $configurator;

    function __construct($router, $requestStack, $configurator)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->configurator = $configurator;
    }

    /**
     * @param Location $location
     */
    public function resolve(Location $location)
    {
        if ($location->isResolved()) {
            return;
        }

        if (!$location->getApiService()) {
            $location->setApiService($this->requestStack->getCurrentRequest()->get(ApiConfigurator::SERVICE_ID));
        }

        $routeName = $this->configurator->getRestRouteNameFor($location->getApiService(), $location->getApiMethod());

        $url = $this->router->generate($routeName, $location->getParametersAsArray(), Router::ABSOLUTE_PATH);

        $location->setUrl($url);
    }

}