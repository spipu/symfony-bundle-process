<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\DependencyInjection;

use Spipu\ProcessBundle\Entity\Process\Input;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SpipuProcessConfiguration implements ConfigurationInterface
{
    /**
     * Build the config tree
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('processs');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->normalizeKeys(true)
            ->useAttributeAsKey('code')
            ->arrayPrototype()
                ->append($this->addParametersNode())
                ->children()
                    ->arrayNode('options')
                        ->isRequired()
                        ->children()
                            ->booleanNode('can_be_put_in_queue')
                                ->isRequired()
                            ->end()
                            ->booleanNode('can_be_rerun_automatically')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('inputs')
                        ->arrayPrototype()
                            ->beforeNormalization()
                                ->ifString()
                                ->then(
                                    function ($v) {
                                        return ['type' => $v];
                                    }
                                )
                            ->end()
                            ->children()
                                ->enumNode('type')
                                    ->values(Input::AVAILABLE_TYPES)
                                    ->isRequired()
                                ->end()
                                ->scalarNode('options')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('name')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('steps')
                        ->isRequired()
                        ->requiresAtLeastOneElement()
                        ->normalizeKeys(true)
                        ->useAttributeAsKey('code')
                        ->arrayPrototype()
                            ->append($this->addParametersNode())
                            ->children()
                                ->scalarNode('class')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function addParametersNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('parameters');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('variable')->end()
        ;

        return $rootNode;
    }
}
