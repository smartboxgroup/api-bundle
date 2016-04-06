<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap;

use BeSimple\SoapBundle\ServiceDefinition\ComplexType;
use BeSimple\SoapBundle\Util\Collection;
use BeSimple\SoapCommon\Definition\Type\TypeRepository;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Serializer;
use Smartbox\ApiBundle\Services\Soap\ComplexTypeLoader;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;

/**
 * Class ComplexTypeLoaderTest
 */
class ComplexTypeLoaderTest extends BaseKernelTestCase
{
    /** @var  Reader */
    protected $annotationReader;

    /** @var  TypeRepository */
    protected $typeRepository;

    /** @var ComplexTypeLoader */
    protected $complexTypeLoader;

    public function setUp()
    {
        $this->bootKernel();
        $this->annotationReader = $this->getContainer()->get('annotation_reader');
        $this->typeRepository = $this->getMockBuilder(TypeRepository::class)->getMock();
        $this->complexTypeLoader = new ComplexTypeLoader($this->annotationReader, $this->typeRepository);
        /** @var Serializer $serializer */
        $serializer = $this->getContainer()->get('serializer');
        $this->complexTypeLoader->setSerializer($serializer);
    }

    public function loadsResourceProvider()
    {
        return [
            'Resource is not an array' => [null],

            'Resource does not have "phpType" defined' => [
                [
                    'group'   => null,
                    'version' => 'v0',
                ]
            ],

            'Resource does not have "group" defined' => [
                [
                    'phpType' => '\Invalid\Class\That\Does\Not\Even\Exists',
                    'version' => 'v0',
                ]
            ],

            'Resource does not have "version" defined' => [
                [
                    'phpType' => '\Invalid\Class\That\Does\Not\Even\Exists',
                    'group'   => null,
                ]
            ],
        ];
    }

    /**
     * @dataProvider loadsResourceProvider
     *
     * @param mixed $resource
     */
    public function testIfResoourceIsDefined($resource)
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->complexTypeLoader->load($resource);
    }

    public function testItShouldFailWhenVersionIsNull()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->complexTypeLoader->load([
            'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
            'group'   => null,
            'version' => null,
        ]);
    }

    public function testItShouldFailWhenLoadingDataForInvalidClass()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->complexTypeLoader->load([
            'phpType' => '\Invalid\Class\That\Does\Not\Even\Exists',
            'group'   => null,
            'version' => 'v0',
        ]);
    }

    public function testItLoadsAnnotationAlias()
    {
        $data = [
            'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
            'group'   => null,
            'version' => 'v0'
        ];

        $annotations = $this->complexTypeLoader->load($data);

        $this->assertSame('Box', $annotations['alias']);
    }

    public function loadsMetadataProvider()
    {
        return [
            'Test notBlank validation when it is only defined for a specific group' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Item',
                    'group'   => null,
                    'version' => 'v0',
                ],
                null,
                [
                    'id'          => $this->newElement('id', 'int', true),
                    'name'        => $this->newElement('name', 'string', false),
                    'description' => $this->newElement('description', 'string', false),
                    'type'        => $this->newElement('type', 'string', false),
                    'entityGroup' => $this->newElement('entityGroup', 'string', true),
                    'version'     => $this->newElement('version', 'string', true),
                ]
            ],

            'Test notNull validation when it is only defined for a specific group' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Product',
                    'group'   => null,
                    'version' => 'v0',
                ],
                null,
                [
                    'id'          => $this->newElement('id', 'int', true),
                    'name'        => $this->newElement('name', 'string', false),
                    'languages'   => $this->newElement('languages', 'string[]', false),
                    'entityGroup' => $this->newElement('entityGroup', 'string', true),
                    'version'     => $this->newElement('version', 'string', true),
                ]
            ],

            'Test if some properties are defined for a specific group' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                    'group'   => 'list',
                    'version' => 'v0',
                ],
                null,
                [
                    'id'           => $this->newElement('id', 'int', false),
                    'status'       => $this->newElement('status', 'string', false),
                    'last_updated' => $this->newElement('last_updated', 'dateTime', false),
                ]
            ],

            'Test the properties defined until a specific version' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Item',
                    'group'   => 'public',
                    'version' => 'v1',
                ],
                null,
                [
                    'id'          => $this->newElement('id', 'int', false),
                    'name'        => $this->newElement('name', 'string', false),
                    'description' => $this->newElement('description', 'string', false),
                ]
            ],

            'Test the properties defined since a specific version' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                    'group'   => 'list',
                    'version' => 'v2',
                ],
                null,
                [
                    'id'           => $this->newElement('id', 'int', false),
                    'description'  => $this->newElement('description', 'string', false),
                    'status'       => $this->newElement('status', 'string', false),
                    'last_updated' => $this->newElement('last_updated', 'dateTime', false),
                ]
            ],

            'Test a property defined as an array' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Product',
                    'group'   => 'list',
                    'version' => 'v0',
                ],
                null,
                ['languages' => $this->newElement('languages', 'string[]', false)]
            ],

            'Test when JMS and BeSimpleSoap annotations are in the same property' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Product',
                    'group'   => 'product',
                    'version' => 'v0',
                ],
                null,
                ['name' => $this->newElement('name', 'string', false)]
            ],

            'Test if group name it does not exist' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Product',
                    'group'   => 'nothing',
                    'version' => 'v0',
                ],
                null,
                []
            ],

            'Test if the entity exist but it does not have any property' => [
                [
                    'phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Nothing',
                    'group'   => null,
                    'version' => 'v0',
                ],
                null,
                []
            ],
        ];
    }

    /**
     * @dataProvider loadsMetadataProvider
     *
     * @param array $data
     * @param string $type
     * @param array $expectedElements
     */
    public function testItLoadsMetadata(array $data, $type, array $expectedElements)
    {
        $annotations = $this->complexTypeLoader->load($data, $type);
        /** @var Collection $properties */
        $properties = $annotations['properties'];
        /** @var ComplexType[] $elements */
        $elements = [];
        foreach ($properties as $id => $element) {
            $elements[$id] = $element;
        }
        $this->assertEquals($expectedElements, $elements);
    }

    /**
     * Helper function to create a new complex type for the data provider
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $nillable
     *
     * @return ComplexType
     */
    private function newElement($name, $value, $nillable)
    {
        $element = new ComplexType();
        $element->setName($name);
        $element->setValue($value);
        $element->setNillable($nillable);

        return $element;
    }

}
