<?php

namespace Smartbox\ApiBundle\Test\Entity;

use Smartbox\ApiBundle\Entity\BasicResponse;

class BasicResponseTest extends \PHPUnit\Framework\TestCase
{
    /** @var BasicResponse */
    private $basicResponse;

    public function getInvalidCodes()
    {
        return [
            ['invalid'],
            [new \stdClass()],
            [[22]],
        ];
    }

    public function getInvalidMessages()
    {
        return [
            [new \stdClass()],
            [['something']],
        ];
    }

    public function setup()
    {
        $this->basicResponse = new BasicResponse();
    }

    public function testItShouldBeConstructedWithParameters()
    {
        $basicResponse = new BasicResponse(17, 'message');
        $this->assertEquals(17, $basicResponse->getCode(), 'The code was not set properly');
        $this->assertEquals('message', $basicResponse->getMessage(), 'The message was not set properly');
    }

    public function testItShouldSetAndGetCode()
    {
        $this->basicResponse->setCode(17);
        $this->assertEquals(17, $this->basicResponse->getCode());
    }

    /**
     * @dataProvider getInvalidCodes
     * @expectedException \InvalidArgumentException
     *
     * @param mixed $code
     */
    public function testItShouldNotAcceptInvalidCodes($code)
    {
        $this->basicResponse->setCode($code);
    }

    public function testItShouldSetAndGetMessage()
    {
        $this->basicResponse->setMessage('bar');
        $this->assertEquals('bar', $this->basicResponse->getMessage());
    }

    /**
     * @dataProvider getInvalidMessages
     * @expectedException \InvalidArgumentException
     *
     * @param mixed $message
     */
    public function testItShouldNotAcceptInvalidMessages($message)
    {
        $this->basicResponse->setMessage($message);
    }
}
