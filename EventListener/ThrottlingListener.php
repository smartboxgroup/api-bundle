<?php

namespace Smartbox\ApiBundle\EventListener;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\EventListener\BaseListener;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;
use Predis\PredisException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\ApiBundle\Exception\ThrottlingException;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class ThrottlingListener.
 */
class ThrottlingListener extends BaseListener implements LoggerAwareInterface
{
    const RATE_LIMIT_INFO = 'rate_limit_info';

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
     * @throws \Exception
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $this->handleOnKernelController($event);
    }

    /**
     * @throws ThrottlingException
     */
    protected function handleOnKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $api = $request->get('api');

        if (
            !(
                ('rest' === $api && HttpKernelInterface::MASTER_REQUEST == $event->getRequestType())
                || ('soap' === $api && HttpKernelInterface::MASTER_REQUEST != $event->getRequestType())
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

        try {
            // Ratelimit the call
            $this->rateLimitService->limitRate($key);
            $rateLimitInfo = $this->rateLimitService->getStorage()->getRateInfo($key);

            if ($rateLimitInfo) {
                if (time() > $rateLimitInfo->getResetTimestamp()) {
                    $this->rateLimitService->resetRate($key);
                    $rateLimitInfo = null;
                }
            }

            if (!$rateLimitInfo) {
                // Create new rate limit entry for this call
                $rateLimitInfo = $this->rateLimitService->createRate(
                    $key,
                    $rateLimit->getLimit(),
                    $rateLimit->getPeriod()
                );
                if (!$rateLimitInfo) {
                    // @codeCoverageIgnoreStart
                    return;
                    // @codeCoverageIgnoreEnd
                }
            }

            // Store the current rating info in the request attributes
            $request = $event->getRequest();
            $request->attributes->set(self::RATE_LIMIT_INFO, $rateLimitInfo);

            // When we exceeded our limit, return a custom error response
            if ($rateLimitInfo->getCalls() > $rateLimitInfo->getLimit()) {
                $message = $this->getParameter('rate_response_message');
                $code = $this->getParameter('rate_response_code');

                if ('rest' === $api) {
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
                    throw new ThrottlingException($message, $code, $rateLimitInfo, $request->get(ApiConfigurator::SERVICE_ID));
                }
            }
        } catch (PredisException $e) {
            $this->logger->error('Error: Redis service is down: "{message}"', ['message' => $e->getMessage()]);
        }
    }

    /**
     * @return string
     */
    private function getKey(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        $serviceId = $request->get('serviceId');
        $methodName = $request->get('methodName');

        $key = $serviceId.':'.$methodName;

        // Let listeners manipulate the key
        $keyEvent = new GenerateKeyEvent($event->getRequest(), $key);
        $this->eventDispatcher->dispatch(RateLimitEvents::GENERATE_KEY, $keyEvent);

        return $keyEvent->getKey();
    }
}
