<?php
namespace FuentesWorks\FiftyOneDegreesBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;


class FiftyOneDegreesExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->process($configuration->getConfigTreeBuilder(), $configs);

        $container->getDefinition('fiftyonedegrees')
            ->addArgument($config['data_file_path']);
    }
}