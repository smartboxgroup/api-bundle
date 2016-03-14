<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapServer\SoapServerBuilder;

/**
 * Class ServerBuilder
 *
 * @package \Smartbox\ApiBundle\Services\Soap
 */
class ServerBuilder extends SoapServerBuilder
{
    /** @var string */
    protected $serverClass = Server::class;

    /**
     * @param $serverClass
     *
     * @return $this
     */
    public function setServerClass($serverClass)
    {
        $this->serverClass = $serverClass;
        return $this;
    }

    /**
     * @return \SoapServer
     */
    public function build()
    {
        $this->validateOptions();

        use_soap_error_handler($this->errorReporting);

        /** @var \SoapServer $server */
        $server = new $this->serverClass($this->wsdl, $this->getSoapOptions());

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
