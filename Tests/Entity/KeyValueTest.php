<?php

namespace Smartbox\ApiBundle\Entity\Test;


use Smartbox\ApiBundle\Entity\KeyValue;

class KeyValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var KeyValue
     */
    private $keyValue;

    public function setup()
    {
        $this->keyValue = new KeyValue();
    }

    public function itShouldBeConstructedWithArguments()
    {
        $keyValue = new KeyValue('foo', 'bar');
        $this->assertEquals('foo', $keyValue->getKey(), 'Key was not set correctly');
        $this->assertEquals('bar', $keyValue->getValue(), 'Value was not set correctly');
    }

    public function itShouldSetAndGetKey()
    {
        $this->keyValue->setKey('foo');
        $this->assertEquals('foo', $this->keyValue->getKey());
    }

    public function itShouldSetAndGetValue()
    {
        $this->keyValue->setValue('bar');
        $this->assertEquals('bar', $this->keyValue->getValue());
    }
}
