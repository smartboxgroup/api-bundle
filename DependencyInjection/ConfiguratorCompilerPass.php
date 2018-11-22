<?php

namespace Smartbox\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ConfiguratorCompilerPass.
 */
class ConfiguratorCompilerPass implements CompilerPassInterface
{
    /** @var ContainerBuilder */
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
        $contextRpc->addMethodCall('setServerBuilder', array(new Reference('smartapi.soap.server.builder')));
        $contextDocument->addMethodCall('setApiConfigurator', array(new Reference('smartapi.configurator')));
        $contextDocument->addMethodCall('setServerBuilder', array(new Reference('smartapi.soap.server.builder')));

        $complexTypeLoader = $container->getDefinition('besimple.soap.definition.loader.annot_complextype');
        $complexTypeLoader->addMethodCall('setSerializer', array(new Reference('jms_serializer')));

        /** @var SmartboxApiExtension $extension */
        $extension = $container->getExtension('smartbox_api');
        $config = $extension->getConfig();
        if ($config['throttling'] && $container->hasDefinition('noxlogic_rate_limit.rate_limit_annotation_listener')) {
            $throttlingListener = $container->getDefinition('noxlogic_rate_limit.rate_limit_annotation_listener');
            $throttlingListener->setClass($container->getParameter('smartapi.throttling_listener.class'));
            $throttlingListener->addMethodCall('setLogger', [new Reference('monolog.logger')]);
        }

        $userProviderId = $config['userProvider'];
        if (!$container->hasDefinition($userProviderId)) {
            throw new InvalidConfigurationException(
                sprintf('No definition found for "%s" used in smartbox_api.userProvider configuration', $userProviderId)
            );
        }

        // securing SOAP API
        $authenticationProviderDef = $container->getDefinition('smartapi.soap.security.authentication.provider');
        $authenticationProviderDef->setArguments([new Reference($userProviderId)]);
    }
}
