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

namespace Spipu\ProcessBundle;

use Spipu\CoreBundle\AbstractBundle;
use Spipu\CoreBundle\Service\RoleDefinitionInterface;
use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ReportManager;
use Spipu\ProcessBundle\Service\RoleDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class SpipuProcessBundle extends AbstractBundle
{
    protected string $extensionAlias = 'spipu_process';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
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
    }

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

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        if ($builder->hasParameter('kernel.environment')) {
            if ($builder->getParameter('kernel.environment') === 'test') {
                $container->import('../config/services_test.yaml');
            }
        }

        foreach ($config as $code => $configValues) {
            $config[$code] = $this->prepareConfig($configValues, $code);
        }

        ksort($config);

        $builder->setParameter('spipu_process', $config);
    }

    /**
     * @param array $process
     * @param string $processCode
     * @return array
     * @throws ProcessException
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     * @SuppressWarnings(PMD.NPathComplexity)
     */
    private function prepareConfig(array $process, string $processCode): array
    {
        $process['code'] = $processCode;

        if ($process['options']['automatic_report']) {
            if (!$process['options']['can_be_put_in_queue']) {
                throw new ProcessException('Option Error - automatic_report can be used only with can_be_put_in_queue');
            }

            $process['inputs'] = [
                    ReportManager::AUTOMATIC_REPORT_EMAIL_FIELD => [
                        'type' => 'string',
                        'required' => true,
                        'allowed_mime_types' => [],
                        'regexp' => null,
                        'help' => null
                    ]
                ] + $process['inputs'];
        }

        foreach ($process['inputs'] as $inputCode => &$input) {
            $input['name'] = $inputCode;
            if (!array_key_exists('allowed_mime_types', $input)) {
                $input['allowed_mime_types'] = [];
            }
            $this->validateConfigInput($input);
        }

        foreach ($process['steps'] as $stepCode => &$step) {
            $step['code'] = $stepCode;
        }

        return $process;
    }

    private function validateConfigInput(array $input): void
    {
        if (count($input['allowed_mime_types']) > 0 && $input['type'] !== 'file') {
            throw new ProcessException('Config Error - allowed_mime_types can be used only with file type');
        }

        if (!empty($input['options']) && $input['type'] === 'file') {
            throw new ProcessException('Config Error - options can not be used with file type');
        }

        if (!empty($input['regexp']) && $input['type'] !== 'string') {
            throw new ProcessException('Config Error - regexp can be used only with string type');
        }
    }

    public function getRolesHierarchy(): RoleDefinitionInterface
    {
        return new RoleDefinition();
    }
}
