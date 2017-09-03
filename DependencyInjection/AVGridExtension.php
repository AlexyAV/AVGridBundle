<?php

namespace AV\GridBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AVGridExtension extends Extension
{
    private $servicesWithCompatibilityIssue = [
        'av_grid.counter_column',
        'av_grid.column',
        'av_grid.action_column',
        'av_grid.view',
        'av_grid.pagination',
        'av_grid.sort',
        'av_grid.query_data_source',
        'av_grid.array_data_source',
        'av_grid.grid_view_factory'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($this->servicesWithCompatibilityIssue as $serviceName) {

            $definition = $container->getDefinition($serviceName);

            if (Kernel::MAJOR_VERSION == 2 && Kernel::MINOR_VERSION < 8) {
                $definition->setScope('prototype');
            } else {
                $definition->setShared(false);
            }
        }

    }
}
