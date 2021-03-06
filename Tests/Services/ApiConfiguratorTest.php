<?php

namespace Smartbox\ApiBundle\Tests\Services;

use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiConfiguratorTest extends BaseKernelTestCase
{
    /** @var ApiConfigurator */
    protected $configurator;

    public function setUp()
    {
        $this->bootKernel();

        /** @var \Metadata\MetadataFactoryInterface $metadataFactory */
        $metadataFactory = $this->getMockBuilder('\Metadata\MetadataFactoryInterface')
            ->setConstructorArgs([get_class($this)])
            ->getMock();
        $this->configurator = new ApiConfigurator(
            $metadataFactory,
            [],
            [],
            [],
            [],
            $this->getContainer()->getParameter('kernel.cache_dir')
        );
    }

    public function validTypesAndGroupsProvider()
    {
        return [
            [Entity::class.ApiConfigurator::$arraySymbol, 'TestGroupA'],
            [Entity::class, 'TestGroupB'],
            [BasicResponse::class, 'TestGroupA'],
            [BasicResponse::class.ApiConfigurator::$arraySymbol, 'TestGroupB'],
            [BasicResponse::class, 'C'],
        ];
    }

    public function invalidCodesArrayInputProvider()
    {
        return [
            [null],
            [['A', 'B']],
            [[45, 45]],
            [[null, null]],
            [['A' => null]],
            [['A' => 12]],
        ];
    }

    public function invalidTypesAndGroupsProvider()
    {
        return [
            [EntityInterface::class, ''],
            ['', 'TestGroupB'],
            ['InexistentClass', 'TestGroupA'],
            [null, 'TestGroupB'],
            [BasicResponse::class, null],
            [EntityInterface::class, 38],
            [13, 'C'],
            [['A', 'B'], 'C'],
        ];
    }

    /**
     * @return array
     */
    public function validCodesArrayInputProvider()
    {
        $data = [
            [
                [
                    'A' => 'XXX-1',
                    'B' => 'XXX-1',
                    'C' => 'XXX-2',
                    'XXX' => 'XXX-3',
                ],
            ],
            [[]],
            [[123213 => 'XXX']],
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function validGetSuccessCodeDescriptionProvider()
    {
        return [
            [
                [
                    'A' => 'XXX-1',
                    'B' => 'XXX-1',
                    'C' => 'XXX-2',
                    'XXX' => 'XXX-3',
                ],
            ],
        ];
    }

    public function notRegisteredAliasProvider()
    {
        return [
            ['INEXISTENT_XXXXX', false],
            [null, false],
            ['', false],
            [21, false],
            [BasicResponse::class, false],
        ];
    }

    /**
     * @dataProvider validTypesAndGroupsProvider
     *
     * @param $type
     * @param $group
     */
    public function testRegisterEntityGroupAliasValid($type, $group)
    {
        $this->configurator->registerEntityGroupAlias($type, $group);

        $type = str_replace(ApiConfigurator::$arraySymbol, '', $type);
        $alias = $type.ucfirst($group);

        $this->assertTrue($this->configurator->isRegisteredAlias($alias));
        $this->assertTrue(class_exists($alias));
        $this->assertTrue(is_a($alias, $type, true));
        $this->assertEquals($type, $this->configurator->getAliasOriginalType($alias));
    }

    /**
     * @dataProvider invalidTypesAndGroupsProvider
     *
     * @param $type
     * @param $group
     */
    public function testRegisterEntityGroupAliasInvalid($type, $group)
    {
        try {
            $this->configurator->registerEntityGroupAlias($type, $group);
            $this->fail('This function call should have failed');
        } catch (\Exception $ex) {
            $this->assertInstanceOf('InvalidArgumentException', $ex);
        }
    }

    /**
     * @dataProvider notRegisteredAliasProvider
     *
     * @param $class
     */
    public function testIsNotRegisteredAlias($class)
    {
        $this->assertFalse($this->configurator->isRegisteredAlias($class));
    }

    /**
     * @dataProvider validGetSuccessCodeDescriptionProvider
     */
    public function testSetGetSuccessCodesDescriptionValid(array $codes)
    {
        $this->configurator->setSuccessCodes($codes);

        foreach ($codes as $code => $desc) {
            $storedDesc = $this->configurator->getSuccessCodeDescription($code);
            $this->assertEquals($storedDesc, $desc);
        }
    }

    public function testSetGetSuccessCodesDescriptionException()
    {
        $this->expectException(\OutOfRangeException::class);

        $this->configurator->setSuccessCodes(['Will throw exception' => 'OutOfRangeException']);
        $this->configurator->getSuccessCodeDescription('Index does not exist');
    }

    /**
     * @dataProvider invalidCodesArrayInputProvider
     *
     * @param $codes
     */
    public function testSetGetSuccessCodesInvalid($codes)
    {
        try {
            $this->configurator->setSuccessCodes($codes);
            $this->fail('This function call should have failed');
        } catch (\Exception $ex) {
            $this->assertInstanceOf('InvalidArgumentException', $ex);
        }
    }

    /**
     * @dataProvider validCodesArrayInputProvider
     *
     * @param $codes
     */
    public function testSetGetErrorCodesValid($codes)
    {
        $this->configurator->setErrorCodes($codes);
        $this->assertEquals($codes, $this->configurator->getErrorCodes());
    }

    /**
     * @dataProvider invalidCodesArrayInputProvider
     *
     * @param $codes
     */
    public function testSetGetErrorCodesInvalid($codes)
    {
        try {
            $this->configurator->setErrorCodes($codes);
            $this->fail('This function call should have failed');
        } catch (\Exception $ex) {
            $this->assertInstanceOf('InvalidArgumentException', $ex);
        }
    }

    public function testGetSingleType()
    {
        $type = $this->configurator->getSingleType('test'.ApiConfigurator::$arraySymbol);
        $this->assertEquals($type, 'test');
    }

    public function testGetCleanParameterValidatesString()
    {
        //test a good case
        $inputName = 'id';
        $type = 'string';
        $value = 'I am a string';
        $param = $this->configurator->getCleanParameter($inputName, $type, $value);
        $this->assertSame($value, $param);

        //test it should throw and helpful bad request exception
        $inputName = 'id';
        $type = 'string';
        $value = ['i' => 'am', 'not' => 'string'];
        $this->expectException(BadRequestHttpException::class);
        $this->configurator->getCleanParameter($inputName, $type, $value);
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
