<?php

namespace Smartbox\ApiBundle\Services\Rest;

use Smartbox\CoreBundle\Exception\ExternalSystemException;
use Smartbox\CoreBundle\Exception\ExternalSystemExceptionInterface;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\Exceptions\RestException;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RestExceptionListener extends ExceptionListener
{
    /**
     * Prevent non rest calls from not being logged
     * @param GetResponseForExceptionEvent $event
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $apiMode = $request->get('api');

        if ($apiMode == 'rest' || empty($apiMode)) {
            /** @var RestException|ExternalSystemExceptionInterface $exception */
            $exception = $event->getException();
            $externalSystemError = ($exception instanceof ExternalSystemExceptionInterface);

            // Sets the expected exception for external system exceptions
            if ($externalSystemError) {
                $externalSystemException = ExternalSystemException::createFromException($exception);
                $event->setException($externalSystemException);
            }

            parent::onKernelException($event);

            // Sets the expected status code and status message for external system exceptions
            if ($externalSystemError) {
                $event->getResponse()->setStatusCode(
                    ExternalSystemExceptionInterface::STATUS_CODE,
                    ExternalSystemExceptionInterface::STATUS_MESSAGE
                );
            }
        }
    }
}
