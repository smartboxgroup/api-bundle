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

    /**
     * @dataProvider loadsMetadataProvider
     *
     * @param array $data
     * @param string $type
     * @param array $expectedElements
     */
    public function testItLoadsMetadata($data, $type, $expectedElements)
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

    public function testItShouldFailWhenLoadingDataForInvalidClass()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->complexTypeLoader->load([
            'phpType' => '\Invalid\Class\That\Does\Not\Even\Exists',
            'group' => null,
            'version' => null,
        ]);
    }

    public function loadsMetadataProvider()
    {
        return [
            [
                ['phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Item', 'group' => null, 'version' => 'v0'],
                null,
                [
                    'id' => $this->newElement('id', 'int', true),
                    'name' => $this->newElement('name', 'string', false),
                    'description' => $this->newElement('description', 'string', false),
                    'entityGroup' => $this->newElement('entityGroup', 'string', true),
                    'version' => $this->newElement('version', 'string', true),
                ]
            ],

            [
                ['phpType' => '\Smartbox\ApiBundle\Tests\Fixtures\Entity\Box', 'group' => 'list', 'version' => 'v0'],
                null,
                [
                    'id' => $this->newElement('id', 'int', false),
                    'status' => $this->newElement('status', 'string', false),
                    'last_updated' => $this->newElement('last_updated', 'dateTime', false),
                ]
            ]
        ];
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
