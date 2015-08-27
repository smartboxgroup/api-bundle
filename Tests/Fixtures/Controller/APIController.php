<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Controller;

use Smartbox\ApiBundle\Entity\Location;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\Box;

class APIController extends \Smartbox\ApiBundle\Controller\APIController
{

    /**
     * @return Box
     */
    protected function getRandomBox(){
        $box = new Box();
        $box->setDescription("lorem ipsum");
        $box->setId(rand(123,999));
        $box->setHeight(rand(50,100));
        $box->setWidth(rand(50,100));
        $box->setLength(rand(50,100));
        $box->setStatus(Box::STATUS_STORED);
        $box->setLastUpdated(new \DateTime());

        return $box;
    }

    public function handleCallAction($serviceId, $serviceName, $methodConfig, $version, $methodName, $input)
    {
        // Checks authorization
        $this->checkAuthorization();

        // Check input
        $inputsConfig = $methodConfig['input'];
        $this->checkInput($version, $inputsConfig, $input);

        $response = null;

        switch($methodName){
            case 'createBox':
                $response = new Location($this->getRandomBox());
                break;

            case 'getBox':
                $response = $this->getRandomBox();
                break;

            case 'updateBox':
            case 'createBoxes':
            case 'setBoxPicked':
            case 'deleteBox':
                $response = null;
                break;
        }

        return $this->respond($response);
    }
}