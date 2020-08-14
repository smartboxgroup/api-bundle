<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Soap;

use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapResponse;
use BeSimple\SoapServer\WsSecurityFilter;

/**
 * Fake class to simulate the behaviour of the real soap security filter based on a callback.
 *
 * Class CallbackSecurityFilter
 */
class FakeCallbackSecurityFilter extends WsSecurityFilter
{
    /** @var callable */
    private $callback;

    /** @var array */
    private $callbackParameters = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
    }

    /**
     * To simulate the original behaviour.
     *
     * @param callable $callback
     */
    public function setUsernamePasswordCallback($callback)
    {
        $this->setCallback($callback);
    }

    /**
     * @param callable $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param array $callbackParameterts
     */
    public function setCallbackParameters($callbackParameterts)
    {
        $this->callbackParameters = $callbackParameterts;
    }

    /**
     * {@inheritdoc}
     */
    public function filterRequest(SoapRequest $request)
    {
        $this->callCallback();
    }

    /**
     * {@inheritdoc}
     */
    public function filterResponse(SoapResponse $response)
    {
        $this->callCallback();
    }

    private function callCallback()
    {
        if (null !== $this->callback) {
            call_user_func_array($this->callback, $this->callbackParameters);
        }
    }
}
