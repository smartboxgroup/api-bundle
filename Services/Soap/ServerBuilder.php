<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapServer\SoapServerBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ServerBuilder
 *
 * @package \Smartbox\ApiBundle\Services\Soap
 */
class ServerBuilder extends SoapServerBuilder implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /** @var string */
    protected $serverClass = Server::class;

    /**
     * @param $serverClass
     *
     * @return $this
     */
    public function setServerClass($serverClass)
    {
        if(!class_exists($serverClass)){
            throw new \InvalidArgumentException("Class not found: ".$serverClass);
        }
        $this->serverClass = $serverClass;
        return $this;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return $this
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return \SoapServer
     */
    public function build()
    {
        $this->validateOptions();

        use_soap_error_handler($this->errorReporting);

        /** @var \SoapServer|ContainerAwareInterface $server */
        $server = new $this->serverClass($this->wsdl, $this->getSoapOptions());
        if ($server instanceof ContainerAwareInterface && $this->container) {
            $server->setContainer($this->container);
        }

        if (null !== $this->persistence) {
            $server->setPersistence($this->persistence);
        }

        if (null !== $this->handlerClass) {
            $server->setClass($this->handlerClass);
        } elseif (null !== $this->handlerObject) {
            $server->setObject($this->handlerObject);
        }

        return $server;
    }
}
