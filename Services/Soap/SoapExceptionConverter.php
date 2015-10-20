<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapServer\Exception\ReceiverSoapFault;
use BeSimple\SoapServer\Exception\SenderSoapFault;
use Smartbox\Integration\FrameworkBundle\Events\Error\SimpleErrorEvent;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidMessageException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SoapExceptionConverter
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $api = $event->getRequest()->get('api');
        $exception = $event->getException();

        // todo: move to api bundle and register this listener with lower priority than LoggingErrorListener, FatalErrorListener and higher than Monolog Exception listener
        if ($api == 'soap') {
            /*
             * The following if statement is a workaround for SOAP fatal issues and the Symfony fatal error handler.
             * When there's a server soap fault a fatal error is raised deep inside the standard php library so Symfony
             * fatal error handler handles it and transforms it to a fatal error exception.
             *
             * Without the following behaviour we will display a standard symfony error, so we need to force it and stop
             * the propagation to display the original soap problem with a valid soap error response.
             */
            if (
                $exception instanceof FatalErrorException &&
                strpos($exception->getMessage(), 'SOAP-ERROR: Encoding') !== FALSE
            ) {
                $event->stopPropagation();
                return;
            }

            if ($exception instanceof UnauthorizedHttpException) {
                $event->setException( new SenderSoapFault('Not authorized'));
                return;
            }

            if ($exception instanceof AuthenticationException) {
                $event->setException( new SenderSoapFault('Authentication failed'));
                return;
            }

            if ($exception instanceof BadRequestHttpException) {
                $event->setException(new SenderSoapFault($exception->getMessage()));
                return;
            }

            if ($exception instanceof InvalidMessageException) {
                $event->setException(new SenderSoapFault($exception->getMessage()));
                return;
            }

            if ($exception instanceof AccessDeniedHttpException) {
                $event->setException(new SenderSoapFault($exception->getMessage()));
                return;
            }

            if(!$exception instanceof \SoapFault) {
                $event->setException(new ReceiverSoapFault("Internal error"));
                return;
            }
        }
    }
}