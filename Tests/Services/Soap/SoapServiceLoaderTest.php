<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap;

use BeSimple\SoapCommon\Classmap;
use BeSimple\SoapCommon\Definition\Definition;
use BeSimple\SoapCommon\Definition\Message;
use BeSimple\SoapCommon\Definition\Type\TypeRepository;
use Metadata\MetadataFactoryInterface;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\ApiBundle\Services\Soap\ComplexTypeLoader;
use Smartbox\ApiBundle\Services\Soap\SoapServiceLoader;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;

class SoapServiceLoaderTest extends BaseKernelTestCase
{
    /**
     * @var ApiConfigurator
     */
    private $apiConfigurator;

    /**
     * @var SoapServiceLoader
     */
    private $soapServiceLoader;

    public function setUp()
    {
        $this->bootKernel();

        $typeRepository = $this->getTypeRepository();

        $complexTypeLoader = new ComplexTypeLoader($this->getContainer()->get('annotation_reader'), $typeRepository);
        $complexTypeLoader->setSerializer($this->getContainer()->get('serializer'));

        $resolver = $this->getMock('Symfony\Component\Config\Loader\LoaderResolverInterface');
        $resolver
            ->method('resolve')
            ->will($this->returnValue($complexTypeLoader));

        $this->apiConfigurator = new ApiConfigurator(
            $this->getMockBuilder(MetadataFactoryInterface::class)->disableOriginalConstructor()->getMock(),
            [],
            [],
            [],
            []
        );

        $this->soapServiceLoader = new SoapServiceLoader($this->apiConfigurator, $typeRepository);
        $this->soapServiceLoader->setResolver($resolver);
    }

    public function testWhenServiceConfigNotHaveMethods()
    {
        $serviceConfig = [
            'eai_v0' => [
                'name'       => 'eai',
                'version'    => 'v0',
                'methods'    => [],
            ]
        ];

        $this->apiConfigurator->setConfig($serviceConfig);

        $serviceDefinition = $this->soapServiceLoader->load('eai_v0');

        $this->assertEmpty($serviceDefinition->getMethods());
    }

    public function testWhenMethodHasOutputButIsNotDefinedCorrectly()
    {
        $serviceConfig = [
            'eai_v0' => [
                'name'       => 'eai',
                'version'    => 'v0',
                'methods'    => [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'body',
                                'group' => 'box',
                            ]
                        ],
                        'output'     => [
                            'type'  => false,
                            'group' => 'public'
                        ]
                    ]
                ]
            ]
        ];

        $this->apiConfigurator->setConfig($serviceConfig);

        $this->setExpectedException(\LogicException::class);

        $this->soapServiceLoader->load('eai_v0');
    }

    public function testWhenInputTypeHasPropertyWithNotValidType()
    {
        $serviceConfig = [
            'eai_v0' => [
                'name'       => 'eai',
                'version'    => 'v0',
                'methods'    => [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxBrief',
                                'mode'  => 'body',
                                'group' => 'public',
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $this->apiConfigurator->setConfig($serviceConfig);

        $this->setExpectedException(\Exception::class);

        $this->soapServiceLoader->load('eai_v0');
    }

    public function serviceConfigProvider()
    {
        return [
            'Test when method does not have defined neither input nor output' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [],
                    ]
                ],
                [
                    'sendBoxBrief' => [
                        'input'  => $this->newMessage('sendBoxBriefRequest', []),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Entity\BasicResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ]
                ]
            ],

            'Test when method has input with body mode' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'body',
                                'group' => 'box',
                            ]
                        ]
                    ]
                ],
                [
                    'sendBoxBrief' => [
                        'input' => $this->newMessage('sendBoxBriefRequest', [
                            [
                                'name'     => 'boxBrief',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxBox',
                                'nillable' => false,
                            ]
                        ]),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Entity\BasicResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ]
                ]
            ],

            'Test when method has input with requirement mode' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'requirement',
                                'group' => 'public',
                            ]
                        ]
                    ]
                ],
                [
                    'sendBoxBrief' => [
                        'input' => $this->newMessage('sendBoxBriefRequest', [
                            [
                                'name'     => 'boxBrief',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'nillable' => false,
                            ]
                        ]),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Entity\BasicResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ]
                ]
            ],

            'Test when method has input with filter mode' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'filter',
                                'group' => 'public',
                            ]
                        ]
                    ]
                ],
                [
                    'sendBoxBrief' => [
                        'input' => $this->newMessage('sendBoxBriefRequest', [
                            [
                                'name'     => 'filters',
                                'type'     => 'BeSimple\SoapCommon\Type\KeyValue\String[]',
                                'nillable' => false,
                            ]
                        ]),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Entity\BasicResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ]
                ]
            ],

            'Test when method has input and output defined' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'body',
                                'group' => 'box',
                            ]
                        ],
                        'output'     => [
                            'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Response',
                            'group' => 'public'
                        ]
                    ]
                ],
                [
                    'sendBoxBrief' => [
                        'input' => $this->newMessage('sendBoxBriefRequest', [
                            [
                                'name'     => 'boxBrief',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxBox',
                                'nillable' => false,
                            ]
                        ]),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\ResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ]
                ]
            ],

            'Test when service has more than one method defined' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'body',
                                'group' => 'box',
                            ]
                        ],
                    ],
                    'sendItemBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'itemBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Item',
                                'mode'  => 'requirement',
                                'group' => 'public',
                            ]
                        ],
                        'output'     => [
                            'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Response',
                            'group' => 'public'
                        ]
                    ]
                ],
                [
                    'sendBoxBrief' => [
                        'input' => $this->newMessage('sendBoxBriefRequest', [
                            [
                                'name'     => 'boxBrief',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxBox',
                                'nillable' => false,
                            ]
                        ]),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Entity\BasicResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ],
                    'sendItemBrief' => [
                        'input' => $this->newMessage('sendItemBriefRequest', [
                            [
                                'name'     => 'itemBrief',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Item',
                                'nillable' => false,
                            ]
                        ]),
                        'output' => $this->newMessage('sendItemBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\ResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ],
                ]
            ],

            'Test when a method has more than one input defined' => [
                [
                    'sendBoxBrief' => [
                        'controller' => 'SmartboxIntegrationPlatformBundle:API:handleCall',
                        'input'      => [
                            'boxBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Box',
                                'mode'  => 'body',
                                'group' => 'box',
                            ],
                            'itemBrief' => [
                                'type'  => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\Item',
                                'mode'  => 'body',
                                'group' => 'public',
                            ],
                        ],
                    ],
                ],
                [
                    'sendBoxBrief' => [
                        'input' => $this->newMessage('sendBoxBriefRequest',
                            [
                                [
                                    'name'     => 'boxBrief',
                                    'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxBox',
                                    'nillable' => false,
                                ],
                                [
                                    'name'     => 'itemBrief',
                                    'type'     => 'Smartbox\ApiBundle\Tests\Fixtures\Entity\ItemPublic',
                                    'nillable' => false,
                                ],
                            ]
                        ),
                        'output' => $this->newMessage('sendBoxBriefResponse', [
                            [
                                'name'     => 'return',
                                'type'     => 'Smartbox\ApiBundle\Entity\BasicResponsePublic',
                                'nillable' => false,
                            ]
                        ]),
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider serviceConfigProvider
     *
     * @param array $methods
     * @param array $expectedResult
     */
    public function testIfServiceDefinitionIsLoadedCorrectly(array $methods, array $expectedResult)
    {
        $serviceConfig = [
            'eai_v0' => [
                'name'       => 'eai',
                'version'    => 'v0',
                'methods'    => $methods
            ]
        ];

        $this->apiConfigurator->setConfig($serviceConfig);

        $serviceDefinition = $this->soapServiceLoader->load('eai_v0');

        $this->assertEquals($expectedResult, $this->getDefinition($serviceDefinition));
    }

    /**
     * Helper method to configure type repository with the basic SOAP types.
     *
     * @return TypeRepository
     */
    private function getTypeRepository()
    {
        $typeRepository  = new TypeRepository(new Classmap());
        $typeRepository->addXmlNamespace('xsd', 'http://www.w3.org/2001/XMLSchema');
        $typeRepository->addType('int', 'xsd:int');
        $typeRepository->addType('string', 'xsd:string');
        $typeRepository->addType('boolean', 'xsd:boolean');
        $typeRepository->addType('int', 'xsd:int');
        $typeRepository->addType('float', 'xsd:float');
        $typeRepository->addType('date', 'xsd:date');
        $typeRepository->addType('dateTime', 'xsd:dateTime');

        return $typeRepository;
    }

    /**
     * Helper method to create a new message.
     *
     * @param string $name Message name.
     * @param array $parts Message parts.
     *
     * @return Message
     */
    private function newMessage($name, array $parts)
    {
        $message = new Message($name);

        foreach ($parts as $part) {
            $message->add($part['name'], $part['type'], $part['nillable']);
        }

        return $message;
    }

    /**
     * Helper method to map the service definition response in an easily way to compare with the expected result.
     *
     * @param Definition $serviceDefinition
     * @return array
     */
    private function getDefinition(Definition $serviceDefinition)
    {
        $definition = [];

        foreach ($serviceDefinition->getMethods() as $method) {
            $name = $method->getName();

            $definition[$name]['input']  = $method->getInput();
            $definition[$name]['output'] = $method->getOutput();
        }

        return $definition;
    }
}