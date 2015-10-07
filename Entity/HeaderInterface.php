<?php

namespace Smartbox\ApiBundle\Entity;


use Smartbox\CoreBundle\Type\EntityInterface;

interface HeaderInterface extends EntityInterface
{

    public function getHeaderName();

    public function getRESTHeaderValue();

    public function isResolved();

}