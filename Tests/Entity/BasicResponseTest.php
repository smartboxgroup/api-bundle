<?php

namespace Smartbox\ApiBundle\Test\Entity;

use Smartbox\ApiBundle\Entity\BasicResponse;

class BasicResponseTest extends \PHPUnit_Framework_TestCase
{
    /** @var BasicResponse */
    private $basicResponse;

    public function getInvalidCodes()
    {
        return [
            ['invalid'],
            [new \stdClass()],
            [[22]]
        ];
    }

    public function getInvalidMessages()
    {
        return [
            [new \stdClass()],
            [['something']]
        ];
    }

    public function setup()
    {
        $this->basicResponse = new BasicResponse();
    }

    /**
     * @test
     */
    public function it_should_be_constructed_with_parameters()
    {
        $basicResponse = new BasicResponse(17, 'message');
        $this->assertEquals(17, $basicResponse->getCode(), 'The code was not set properly');
        $this->assertEquals('message', $basicResponse->getMessage(), 'The message was not set properly');
    }

    /**
     * @test
     */
    public function it_should_set_and_get_code()
    {
        $this->basicResponse->setCode(17);
        $this->assertEquals(17, $this->basicResponse->getCode());
    }

    /**
     * @test
     * @dataProvider getInvalidCodes
     * @expectedException \InvalidArgumentException
     * @param mixed $code
     */
    public function it_should_not_accept_invalid_codes($code)
    {
        $this->basicResponse->setCode($code);
    }

    /**
     * @test
     */
    public function it_should_set_ang_get_message()
    {
        $this->basicResponse->setMessage('bar');
        $this->assertEquals('bar', $this->basicResponse->getMessage());
    }

    /**
     * @test
     * @dataProvider getInvalidMessages
     * @expectedException \InvalidArgumentException
     * @param mixed $message
     */
    public function it_should_not_accept_invalid_messages($message)
    {
        $this->basicResponse->setMessage($message);
    }
}
