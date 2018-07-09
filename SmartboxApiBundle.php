<?php

namespace Smartbox\ApiBundle;

use Smartbox\ApiBundle\DependencyInjection\ConfiguratorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SmartboxApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfiguratorCompilerPass());
    }
}
