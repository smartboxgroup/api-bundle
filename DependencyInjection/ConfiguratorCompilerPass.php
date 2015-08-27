<?php
namespace Smartbox\ApiBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfiguratorCompilerPass implements CompilerPassInterface
{

    /** @var  ContainerBuilder */
    protected $container;

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $contextRpc = $container->getDefinition('besimple.soap.context.rpcliteral');
        $contextDocument = $container->getDefinition('besimple.soap.context.documentwrapped');

        $contextRpc->addMethodCall('setApiConfigurator', array(new Reference('smartapi.configurator')));
        $contextDocument->addMethodCall('setApiConfigurator', array(new Reference('smartapi.configurator')));

        $complexTypeLoader = $container->getDefinition('besimple.soap.definition.loader.annot_complextype');
        $complexTypeLoader->addMethodCall('setSerializer', array(new Reference('serializer')));
    }
}