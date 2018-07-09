<?php

namespace Smartbox\ApiBundle\Services\Rest;

use FOS\RestBundle\EventListener\FormatListener as FOSRESTFormatListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Class FormatListener.
 *
 * Class to extend the default FOSRestBundle format listener in order to prevent
 * the application from failing when receiving SOAP requests.
 */
class FormatListener extends FOSRESTFormatListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        try {
            parent::onKernelRequest($event);
        } catch (NotAcceptableHttpException $e) {
        }
    }
}
