<?php

namespace Smartbox\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxApiExtension extends Extension
{
    const SERVICE_ID_FILE_LIST = 'smartapi.user_list.file';
    const SERVICE_ID_USER_PROVIDER = 'smartapi.security.user_provider';

    protected $config;

    protected $unResolvedApiServices = array();
    protected $resolvedApiServices = array();

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function resolveServiceConfig($service, $parent)
    {
        $parentConfig = $this->resolvedApiServices[$parent];
        $serviceConfig = $this->unResolvedApiServices[$service];
        $mergedMethods = array_merge($parentConfig['methods'], $serviceConfig['methods']);

        $serviceConfig['methods'] = array();

        $removedMethods = $serviceConfig['removed'];
        foreach ($mergedMethods as $methodName => $methodConfig) {
            if (!in_array($methodName, $removedMethods)) {
                $serviceConfig['methods'][$methodName] = $methodConfig;
            }
        }

        $this->resolvedApiServices[$service] = $serviceConfig;
        unset($this->unResolvedApiServices[$service]);
    }

    public function processConfig($config)
    {
        $this->config = $config;
        $this->unResolvedApiServices = $this->config['services'];

        $defaultController = $config['default_controller'];

        // Add default controller
        foreach ($this->unResolvedApiServices as $apiService => $serviceConfig) {
            foreach ($serviceConfig['methods'] as $method => $methodConfig) {
                if (!empty($methodConfig) && 'default' === $methodConfig['controller']) {
                    $this->unResolvedApiServices[$apiService]['methods'][$method]['controller'] = $defaultController;
                }
            }
        }

        foreach ($this->unResolvedApiServices as $service => $serviceConfig) {
            // Resolve parent
            if (!array_key_exists('parent', $serviceConfig)) {
                $this->resolvedApiServices[$service] = $serviceConfig;
                unset($this->unResolvedApiServices[$service]);
            } else {
                $parent = $serviceConfig['parent'];
                if (array_key_exists($parent, $this->resolvedApiServices)) {
                    $this->resolveServiceConfig($service, $parent);
                } else {
                    throw new InvalidConfigurationException(
                        "Parent service $parent not resolved. Please define services after their parent, not before"
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configurator = $container->getDefinition('smartapi.configurator');

        $this->processConfig($config);

        if ($config['throttling']) {
            $loader->load('services_throttling.yml');
        }

        if (isset($config['usersFile']) && isset($config['passwordsFile'])) {
            if (!$container->has('cache.app')) {
                $loader->load('services_cache.yml');
            }

            $container->findDefinition(static::SERVICE_ID_FILE_LIST)
                ->setArguments([$config['usersFile'], $config['passwordsFile'], new Reference('cache.app')]);

            $container->findDefinition(static::SERVICE_ID_USER_PROVIDER)
                ->setArguments([new Reference(static::SERVICE_ID_FILE_LIST)]);
        }

        $configurator->addArgument($this->resolvedApiServices);
        $configurator->addArgument($config['successCodes']);
        $configurator->addArgument($config['errorCodes']);
        $configurator->addArgument($config['restEmptyBodyResponseCodes']);
        $configurator->addArgument(new Parameter('kernel.cache_dir'));
        $configurator->addArgument($config['fixturesPath']);
    }
}
