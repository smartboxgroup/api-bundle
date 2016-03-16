<?php

namespace Smartbox\ApiBundle\EventListener;

use BeSimple\SoapServer\Exception\SenderSoapFault;
use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\EventListener\BaseListener;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\CoreBundle\Utils\Monolog\Formatter\JMSSerializerFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ThrottlingListener extends BaseListener
{
    use LoggerAwareTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Noxlogic\RateLimitBundle\Service\RateLimitService
     */
    protected $rateLimitService;

    /**
     * @var \Noxlogic\RateLimitBundle\Util\PathLimitProcessor
     */
    protected $pathLimitProcessor;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param RateLimitService $rateLimitService
     * @param PathLimitProcessor $pathLimitProcessor
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RateLimitService $rateLimitService,
        PathLimitProcessor $pathLimitProcessor
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->rateLimitService = $rateLimitService;
        $this->pathLimitProcessor = $pathLimitProcessor;
    }

    /**
     * @param FilterControllerEvent $event
     * @throws SenderSoapFault
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        /**
         * We must ensure that even if the throttling fails for any reason, we still handle the requests
         */
        try{
            $this->handleOnKernelController($event);
        }catch (\Exception $ex){
            $this->logger->error('Redis service is down: '. $ex->getMessage());
        }
    }

    protected function handleOnKernelController(FilterControllerEvent $event){
        $request = $event->getRequest();
        $api = $request->get('api');

        if (
        ! (
            ($api === 'rest' && $event->getRequestType() == HttpKernelInterface::MASTER_REQUEST)
            || ($api === 'soap' && $event->getRequestType() != HttpKernelInterface::MASTER_REQUEST)
        )
        ) {
            return;
        }

        $methodConfig = $request->get('methodConfig');

        if (!array_key_exists('throttling', $methodConfig)) {
            return;
        }

        $rateLimit = new RateLimit($methodConfig['throttling']);

        $key = $this->getKey($event);

        // Ratelimit the call
        $rateLimitInfo = $this->rateLimitService->limitRate($key);
        if (! $rateLimitInfo) {
            // Create new rate limit entry for this call
            $rateLimitInfo = $this->rateLimitService->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
            if (! $rateLimitInfo) {
                // @codeCoverageIgnoreStart
                return;
                // @codeCoverageIgnoreEnd
            }
        }


        // Store the current rating info in the request attributes
        $request = $event->getRequest();
        $request->attributes->set('rate_limit_info', $rateLimitInfo);

        // When we exceeded our limit, return a custom error response
        if ($rateLimitInfo->getCalls() > $rateLimitInfo->getLimit()) {
            $message = $this->getParameter('rate_response_message');
            $code = $this->getParameter('rate_response_code');

            if ($api === 'rest') {
                // Throw an exception if configured.
                if ($this->getParameter('rate_response_exception')) {
                    $class = $this->getParameter('rate_response_exception');
                    throw new $class($this->getParameter('rate_response_message'), $this->getParameter('rate_response_code'));
                }

                $event->setController(
                    function () use ($message, $code) {
                        // @codeCoverageIgnoreStart
                        return new Response($message, $code);
                        // @codeCoverageIgnoreEnd
                    }
                );
            } else {
                throw new SenderSoapFault($message);
            }
        }
    }

    private function getKey(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        $serviceId = $request->get('serviceId');
        $methodName = $request->get('methodName');

        $key = $serviceId . ':' . $methodName;

        // Let listeners manipulate the key
        $keyEvent = new GenerateKeyEvent($event->getRequest(), $key);
        $this->eventDispatcher->dispatch(RateLimitEvents::GENERATE_KEY, $keyEvent);

        return $keyEvent->getKey();
    }
}