<?php

namespace Smartbox\ApiBundle\Tests\Services;


use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\CoreBundle\Entity\Entity;

class ApiConfiguratorTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ApiConfigurator */
    protected $configurator;

    public function setUp()
    {
        $this->configurator = new ApiConfigurator(array(), array(), array());
    }

    public function validTypesAndGroupsProvider()
    {
        return array(
            array(Entity::class.ApiConfigurator::$arraySymbol, 'TestGroupA'),
            array(Entity::class, 'TestGroupB'),
            array(BasicResponse::class, 'TestGroupA'),
            array(BasicResponse::class.ApiConfigurator::$arraySymbol, 'TestGroupB'),
            array(BasicResponse::class, 'C'),
        );
    }

    public function invalidCodesArrayInputProvider()
    {
        return array(
            array(null),
            array(array('A', 'B')),
            array(array(45, 45)),
            array(array(null, null)),
            array(array('A' => null)),
            array(array('A' => 12))
        );
    }

    public function invalidTypesAndGroupsProvider()
    {
        return array(
            array(Entity::class, ''),
            array('', 'TestGroupB'),
            array('InexistentClass', 'TestGroupA'),
            array(null, 'TestGroupB'),
            array(BasicResponse::class, null),
            array(Entity::class, 38),
            array(13, 'C'),
            array(array('A', 'B'), 'C'),
        );
    }

    public function validCodesArrayInputProvider()
    {
        $data = array(
            array(
                array(
                    'A' => 'XXX-1',
                    'B' => 'XXX-1',
                    'C' => 'XXX-2',
                    'XXX' => 'XXX-3',
                )
            ),
            array(array()),
            array(array(123213 => 'XXX'))
        );

        return $data;
    }

    public function notRegisteredAliasProvider()
    {
        return array(
            array("INEXISTENT_XXXXX", false),
            array(null, false),
            array("", false),
            array(21, false),
            array(BasicResponse::class, false),
        );
    }

    /**
     * @dataProvider validTypesAndGroupsProvider
     * @param $type
     * @param $group
     */
    public function testRegisterEntityGroupAliasValid($type, $group)
    {
        $this->configurator->registerEntityGroupAlias($type, $group);

        $type = str_replace(ApiConfigurator::$arraySymbol, "", $type);
        $alias = $type.ucfirst($group);

        $this->assertTrue($this->configurator->isRegisteredAlias($alias));
        $this->assertTrue(class_exists($alias));
        $this->assertTrue(is_a($alias, $type, true));
        $this->assertEquals($type, $this->configurator->getAliasOriginalType($alias));
    }

    /**
     * @dataProvider invalidTypesAndGroupsProvider
     * @param $type
     * @param $group
     */
    public function testRegisterEntityGroupAliasInvalid($type, $group)
    {
        try {
            $this->configurator->registerEntityGroupAlias($type, $group);
            $this->fail("This function call should have failed");
        } catch (\Exception $ex) {
            $this->assertInstanceOf('InvalidArgumentException', $ex);
        }
    }

    /**
     * @dataProvider notRegisteredAliasProvider
     * @param $class
     */
    public function testIsNotRegisteredAlias($class)
    {
        $this->assertFalse($this->configurator->isRegisteredAlias($class));
    }


    /**
     * @dataProvider validCodesArrayInputProvider
     * @param $codes
     */
    public function testSetGetSuccessCodesValid($codes)
    {
        $this->configurator->setSuccessCodes($codes);

        foreach ($codes as $code => $desc) {
            $storedDesc = $this->configurator->getSuccessCodeDescription($code);
            $this->assertEquals($storedDesc, $desc);
        }
    }

    /**
     * @dataProvider invalidCodesArrayInputProvider
     * @param $codes
     */
    public function testSetGetSuccessCodesInvalid($codes)
    {
        try {
            $this->configurator->setSuccessCodes($codes);
            $this->fail("This function call should have failed");
        } catch (\Exception $ex) {
            $this->assertInstanceOf('InvalidArgumentException', $ex);
        }
    }

    /**
     * @dataProvider validCodesArrayInputProvider
     * @param $codes
     */
    public function testSetGetErrorCodesValid($codes)
    {
        $this->configurator->setErrorCodes($codes);
        $this->assertEquals($codes, $this->configurator->getErrorCodes());
    }

    /**
     * @dataProvider invalidCodesArrayInputProvider
     * @param $codes
     */
    public function testSetGetErrorCodesInvalid($codes)
    {
        try {
            $this->configurator->setErrorCodes($codes);
            $this->fail("This function call should have failed");
        } catch (\Exception $ex) {
            $this->assertInstanceOf('InvalidArgumentException', $ex);
        }
    }

    public function testGetSingleType(){
        $type = $this->configurator->getSingleType('test'.ApiConfigurator::$arraySymbol);
        $this->assertEquals($type,'test');
    }


    /* TODO:
     * getSoapTypeFor
     * isHeaderType, isEntityOrArrayOfEntities, isEntityOrArrayOfHeaders, isEntity, getSingleType, getJMSSingleType, getJMSType
     * getCleanParameter
     * getRestRouteNameFor
     * setConfig, getConfig, hasService
     *
     */
}