<?php

namespace Smartbox\ApiBundle\Utils\SmokeTest;

use Predis\Client;
use Predis\PredisException;
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

        try {
            /** @var \Predis\Response\Status $pingInfo */
            $pingInfo = $this->redis->ping();
            if ($pingInfo->getPayload() === 'PONG') {
                $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_SUCCESS);
                $smokeTestOutput->addMessage('Connection checked');
            } else {
                $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
                $smokeTestOutput->addMessage('Could not connect to redis server.');
            }
        } catch (PredisException $e) {
            $smokeTestOutput->setCode(SmokeTestOutput::OUTPUT_CODE_FAILURE);
            $smokeTestOutput->addMessage('Could not connect to redis server. Error: ' . $e->getMessage());
        }

//        $serverInfo = $this->redis->info();
//        $smokeTestOutput->addMessage('Server info: ' . var_export($serverInfo, true));

        return $smokeTestOutput;
    }
}