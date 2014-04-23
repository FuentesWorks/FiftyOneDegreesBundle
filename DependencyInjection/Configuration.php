<?php
namespace FuentesWorks\FiftyOneDegreesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return \Symfony\Component\Config\Definition\NodeInterface
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fiftyonedegrees');

        $rootNode
            ->children()
            ->scalarNode('data_file_path')->defaultValue(null)->end()
            ->end();

        return $treeBuilder->buildTree();
    }
}