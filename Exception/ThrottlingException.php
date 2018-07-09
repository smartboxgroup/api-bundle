<?php

namespace Smartbox\ApiBundle\Exception;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class ThrottlingException extends \Exception
{
    /**
     * @var RateLimitInfo
     */
    protected $rateLimitInfo;

    /**
     * @var string
     */
    protected $serviceId;

    /**
     * ThrottlingException constructor.
     *
     * @param string        $message
     * @param int           $code
     * @param RateLimitInfo $rateLimitInfo
     * @param $serviceId
     * @param \Exception|null $previous
     */
    public function __construct($message, $code, RateLimitInfo $rateLimitInfo, $serviceId, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->rateLimitInfo = $rateLimitInfo;
        $this->serviceId = $serviceId;
    }

    /**
     * @return RateLimitInfo
     */
    public function getRateLimitInfo()
    {
        return $this->rateLimitInfo;
    }

    /**
     * @param RateLimitInfo $rateLimitInfo
     */
    public function setRateLimitInfo($rateLimitInfo)
    {
        $this->rateLimitInfo = $rateLimitInfo;
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param string $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }
}
