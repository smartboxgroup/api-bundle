<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap;

use BeSimple\SoapCommon\Definition\Type\TypeRepository;
use Doctrine\Common\Annotations\Reader;
use Smartbox\ApiBundle\Services\Soap\ComplexTypeLoader;

/**
 * Class ComplexTypeLoaderTest
 */
class ComplexTypeLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Reader */
    protected $annotationReader;

    /** @var  TypeRepository */
    protected $typeRepository;

    /** @var ComplexTypeLoader */
    protected $complexTypeLoader;

    public function setUp()
    {
        $this->annotationReader = $this->getMockBuilder(Reader::class)->getMock();
        $this->typeRepository = $this->getMockBuilder(TypeRepository::class)->getMock();
        $this->complexTypeLoader = new ComplexTypeLoader($this->annotationReader, $this->typeRepository);
    }

    /**
     * @dataProvider loadsMetadataProvider
     *
     * @param array $data
     * @param string $type
     * @param array $expectedAnnotations
     */
    public function testItLoadsMetadata($data, $type, $expectedAnnotations)
    {
        $annotations = $this->complexTypeLoader->load($data, $type);
        $this->assertEquals($expectedAnnotations, $annotations);
    }

    public function testItShouldFailWhenLoadingDataForInvalidClass()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->complexTypeLoader->load([
            'phpType' => '\Invalid\Class\That\Does\Not\Even\Exists'
        ]);
    }

    public function loadsMetadataProvider()
    {
        // TODO
        return [];
    }

}
