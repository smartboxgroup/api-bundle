<?php

namespace Smartbox\ApiRestClient;

/**
 * Class ApiRestResponse
 *
 * @package Smartbox\ApiRestClient
 */
class ApiRestResponse
{
    const TRANSACTION_ID              = "X-Transaction-Id";
    const RATE_LIMIT_REMAINING        = "X-RateLimit-Remaining";
    const RATE_LIMIT_RESET            = "X-RateLimit-Reset";
    const RATE_LIMIT_LIMIT            = "X-RateLimit-Limit";
    const RATE_LIMIT_RESET_REMAINING  = "X-RateLimit-Reset-Remaining";

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var mixed
     */
    protected $body;

    /**
     * @var string
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $rateLimitRemaining;

    /**
     * @var string
     */
    protected $rateLimitReset;

    /**
     * @var string
     */
    protected $rateLimitLimit;

    /**
     * @var string
     */
    protected $rateLimitResetRemaining;

    /**
     * @return mixed
     */
    public function getTransactionId ()
    {
        return $this->transactionId;
    }

    /**
     * @param mixed $transactionId
     */
    public function setTransactionId ($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return mixed
     */
    public function getRateLimitRemaining ()
    {
        return $this->rateLimitRemaining;
    }

    /**
     * @param mixed $rateLimitRemaining
     */
    public function setRateLimitRemaining ($rateLimitRemaining)
    {
        $this->rateLimitRemaining = $rateLimitRemaining;
    }

    /**
     * @return mixed
     */
    public function getRateLimitReset ()
    {
        return $this->rateLimitReset;
    }

    /**
     * @param mixed $rateLimitReset
     */
    public function setRateLimitReset ($rateLimitReset)
    {
        $this->rateLimitReset = $rateLimitReset;
    }

    /**
     * @return mixed
     */
    public function getRateLimitLimit ()
    {
        return $this->rateLimitLimit;
    }

    /**
     * @param mixed $rateLimitLimit
     */
    public function setRateLimitLimit ($rateLimitLimit)
    {
        $this->rateLimitLimit = $rateLimitLimit;
    }

    /**
     * @return mixed
     */
    public function getRateLimitResetRemaining ()
    {
        return $this->rateLimitResetRemaining;
    }

    /**
     * @param mixed $rateLimitResetRemaining
     */
    public function setRateLimitResetRemaining ($rateLimitResetRemaining)
    {
        $this->rateLimitResetRemaining = $rateLimitResetRemaining;
    }

    /**
     * @return array
     */
    public function getHeaders ()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders ($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return mixed
     */
    public function getBody ()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody ($body)
    {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getStatusCode ()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode ($statusCode)
    {
        $this->statusCode = $statusCode;
    }
}