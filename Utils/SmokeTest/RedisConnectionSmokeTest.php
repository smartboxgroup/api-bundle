<?php

namespace Smartbox\ApiBundle\Utils\SmokeTest;

use Predis\Client;
use Smartbox\CoreBundle\Utils\SmokeTest\SmokeTestInterface;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;

class RedisConnectionSmokeTest implements SmokeTestInterface
{
    /**
     * @var Client
     */
    protected $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    public function getDescription()
    {
        return 'SmokeTest to check connection of redis.';
    }

    public function run()
    {
        $smokeTestOutput = new SmokeTestOutput();

        /** @var \Predis\Response\Status $pingInfo */
        $pingInfo = $this->redis->ping();
        if ($pingInfo->getPayload() === 'PONG') {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_SUCCESS);
            $smokeTestOutput->addMessage('Connection checked');
        } else {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
            $smokeTestOutput->addMessage('Could not connect to redis server.');
        }

//        $serverInfo = $this->redis->info();
//        $smokeTestOutput->addMessage('Server info: ' . var_export($serverInfo, true));

        return $smokeTestOutput;
    }
}