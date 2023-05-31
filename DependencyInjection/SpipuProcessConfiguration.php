<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
        $treeBuilder = new TreeBuilder('process');

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
                            ->booleanNode('process_lock_on_failed')
                                ->defaultTrue()
                            ->end()
                            ->arrayNode('process_lock')
                                ->beforeNormalization()->castToArray()->end()
                                ->scalarPrototype()->cannotBeEmpty()->end()
                            ->end()
                            ->scalarNode('needed_role')
                                ->defaultNull()
                            ->end()
                            ->booleanNode('automatic_report')
                                ->defaultFalse()
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
                                ->booleanNode('required')
                                    ->defaultTrue()
                                ->end()
                                ->arrayNode('allowed_mime_types')
                                    ->beforeNormalization()->castToArray()->end()
                                    ->scalarPrototype()->end()
                                ->end()
                                ->scalarNode('regexp')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('help')
                                    ->defaultNull()
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
                            ->children()
                                ->booleanNode('ignore_in_progress')
                                    ->defaultFalse()
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
