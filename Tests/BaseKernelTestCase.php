<?php

namespace Smartbox\ApiBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BaseKernelTestCase
 * @package Smartbox\ApiBundle\Tests
 */
class BaseKernelTestCase extends KernelTestCase
{
    public function getContainer()
    {
        return self::$kernel->getContainer();
    }

    public function setUp()
    {
        $this->bootKernel();
    }
}