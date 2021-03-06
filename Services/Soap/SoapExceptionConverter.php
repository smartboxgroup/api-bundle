<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapServer\Exception\ReceiverSoapFault;
use BeSimple\SoapServer\Exception\SenderSoapFault;
use Psr\Log\LoggerInterface;
use Smartbox\ApiBundle\EventListener\ThrottlingListener;
use Smartbox\ApiBundle\Exception\ThrottlingException;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class SoapExceptionConverter.
 */
class SoapExceptionConverter
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * SoapExceptionConverter constructor.
     */
    public function __construct(LoggerInterface $logger, RequestStack $requestStack)
    {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $api = $event->getRequest()->get('api');
        $exception = $event->getException();

        if ('soap' == $api) {
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
                false !== strpos($exception->getMessage(), 'SOAP-ERROR: Encoding')
            ) {
                $event->setException($this->createSoapFault(SenderSoapFault::class, $exception->getMessage()));

                return;
            }

            if (
                $exception instanceof FatalErrorException &&
                false !== strpos($exception->getMessage(), 'Error: Procedure')
            ) {
                $event->setException($this->createSoapFault(SenderSoapFault::class, $exception->getMessage()));

                return;
            }

            if ($exception instanceof UnauthorizedHttpException) {
                $event->setException($this->createSoapFault(SenderSoapFault::class, 'Not authorized'));

                return;
            }

            if ($exception instanceof AuthenticationException) {
                $event->setException($this->createSoapFault(SenderSoapFault::class, 'Authentication failed'));

                return;
            }

            if (
                $exception instanceof BadRequestHttpException ||
                $exception instanceof AccessDeniedHttpException
            ) {
                $event->setException($this->createSoapFault(SenderSoapFault::class, $exception->getMessage()));

                return;
            }

            if ($exception instanceof ThrottlingException) {
                $request = $event->getRequest();
                $rateLimitInfo = $exception->getRateLimitInfo();

                //Set mandatory variable missing inside the request
                $request->attributes->set(ApiConfigurator::SERVICE_ID, $exception->getServiceId());
                $request->attributes->set(ThrottlingListener::RATE_LIMIT_INFO, $rateLimitInfo);

                $event->setException($this->createSoapFault(SenderSoapFault::class, $exception->getMessage()));

                return;
            }

            if (!$exception instanceof \SoapFault) {
                $this->logger->error(
                    sprintf(
                        'Raised "%s" in SOAP mode with message: "%s"',
                        get_class($exception),
                        $exception->getMessage()
                    ),
                    ['exception' => $exception]
                );
                $event->setException($this->createSoapFault(ReceiverSoapFault::class, 'Internal error'));

                return;
            }
        }
    }

    /**
     * @param string $class
     * @param string $message
     * @param string $code
     * @param string $actor
     * @param array  $detail
     *
     * @return \SoapFault
     */
    protected function createSoapFault($class, $message, $code = '', $actor = null, $detail = [])
    {
        switch ($class) {
            case SenderSoapFault::class:
            case ReceiverSoapFault::class:
                return new $class($message, $actor, $detail);
                break;
            case \SoapFault::class:
                return new $class($code, $message, $actor, $detail);
                break;
        }

        // if another class is given defaults to a simple generic SoapFault
        return new \SoapFault($code, 'SOAP-ERROR: '.$message, $actor, $detail);
    }
}
