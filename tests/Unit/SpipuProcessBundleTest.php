<?php
namespace Spipu\ProcessBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Spipu\ConfigurationBundle\SpipuConfigurationBundle;
use Spipu\CoreBundle\RolesHierarchyBundleInterface;
use Spipu\CoreBundle\Service\RoleDefinitionInterface;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\SpipuProcessBundle;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Spipu\UiBundle\Form\Options\YesNo;
use Symfony\Component\Config\Definition\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface;

class SpipuProcessBundleTest extends TestCase
{
    public function testBase()
    {
        $builder = SymfonyMock::getContainerBuilder($this);
        $configurator = SymfonyMock::getContainerConfigurator($this);
        $bundle = new SpipuProcessBundle();

        $this->assertInstanceOf(ConfigurableExtensionInterface::class, $bundle);

        $this->assertSame('spipu_process', $bundle->getContainerExtension()->getAlias());

        $this->assertInstanceOf(RolesHierarchyBundleInterface::class, $bundle);
        $this->assertInstanceOf(RoleDefinitionInterface::class, $bundle->getRolesHierarchy());

        $this->assertFalse($builder->hasParameter('spipu_process'));
        $bundle->loadExtension([], $configurator, $builder);
        $this->assertTrue($builder->hasParameter('spipu_process'));
        $this->assertSame([], $builder->getParameter('spipu_process'));
    }

    public function testLoadOk()
    {
        $configs = SpipuProcessMock::getConfigurationSampleData();
        $workflows = SpipuProcessMock::getConfigurationSampleDataBuilt();

        $configurator = SymfonyMock::getContainerConfigurator($this);
        $builder = SymfonyMock::getContainerBuilder($this);
        $builder
            ->expects($this->once())
            ->method('setParameter')
            ->with('spipu_process', $workflows);

        $bundle = new SpipuProcessBundle();
        $bundle->loadExtension($configs, $configurator, $builder);
    }

    public function testLoadKoMimeTypeWithoutFile()
    {
        $configs = [
            'test' => [
                'name' => 'Test KO 1',
                'options' => [
                    'can_be_put_in_queue' => false,
                    'can_be_rerun_automatically' => false,
                    'process_lock_on_failed' => true,
                    'process_lock' => [],
                    'needed_role' => null,
                    'automatic_report' => false,
                ],
                'inputs' => [
                    'good_input' => ['type' => 'string', 'required' => true],
                    'bad_input' => ['type' => 'string', 'allowed_mime_types' => ['csv']],
                ],
                'parameters' => [],
                'steps' => [
                    'first' => [
                        'class' => SpipuProcessMock::COUNT_CLASSNAME,
                        'parameters' => [
                            'string' => '{{ good_input }} first',
                            'array'  => [1],
                        ],
                        'ignore_in_progress' => false,
                    ],
                ],
            ]
        ];

        $configurator = SymfonyMock::getContainerConfigurator($this);

        $builder = SymfonyMock::getContainerBuilder($this);
        $builder
            ->expects($this->never())
            ->method('setParameter');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Config Error - allowed_mime_types can be used only with file type');

        $bundle = new SpipuProcessBundle();
        $bundle->loadExtension($configs, $configurator, $builder);
    }

    public function testLoadKoRegexpWithoutString()
    {
        $configs = [
            'test' => [
                'name' => 'Test KO 1',
                'options' => [
                    'can_be_put_in_queue' => false,
                    'can_be_rerun_automatically' => false,
                    'process_lock_on_failed' => true,
                    'process_lock' => [],
                    'needed_role' => null,
                    'automatic_report' => false,
                ],
                'inputs' => [
                    'good_input' => ['type' => 'string', 'required' => true],
                    'bad_input' => ['type' => 'file', 'regexp' => 'foo'],
                ],
                'parameters' => [],
                'steps' => [
                    'first' => [
                        'class' => SpipuProcessMock::COUNT_CLASSNAME,
                        'parameters' => [
                            'string' => '{{ good_input }} first',
                            'array'  => [1],
                        ],
                        'ignore_in_progress' => false,
                    ],
                ],
            ]
        ];

        $configurator = SymfonyMock::getContainerConfigurator($this);
        $builder = SymfonyMock::getContainerBuilder($this);

        $builder
            ->expects($this->never())
            ->method('setParameter');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Config Error - regexp can be used only with string type');

        $bundle = new SpipuProcessBundle();
        $bundle->loadExtension($configs, $configurator, $builder);
    }

    public function testLoadKoOptionWithFile()
    {
        $configs = [
            'test' => [
                'name' => 'Test KO 2',
                'options' => [
                    'can_be_put_in_queue' => false,
                    'can_be_rerun_automatically' => false,
                    'process_lock_on_failed' => true,
                    'process_lock' => [],
                    'needed_role' => null,
                    'automatic_report' => false,
                ],
                'inputs' => [
                    'good_input' => ['type' => 'string', 'required' => true],
                    'bad_input' => ['type' => 'file', 'options' => YesNo::class],
                ],
                'parameters' => [],
                'steps' => [
                    'first' => [
                        'class' => SpipuProcessMock::COUNT_CLASSNAME,
                        'parameters' => [
                            'string' => '{{ good_input }} first',
                            'array'  => [1],
                        ],
                        'ignore_in_progress' => false,
                    ],
                ],
            ]
        ];

        $configurator = SymfonyMock::getContainerConfigurator($this);

        $builder = SymfonyMock::getContainerBuilder($this);
        $builder
            ->expects($this->never())
            ->method('setParameter');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Config Error - options can not be used with file type');

        $bundle = new SpipuProcessBundle();
        $bundle->loadExtension($configs, $configurator, $builder);
    }

    public function testConfigurationMissingWorkflowName()
    {
        $configs = [
            0 => [
                'test' => [
                    'steps' => [
                        'first' => [
                            'class' => SpipuProcessMock::COUNT_CLASSNAME,
                        ]
                    ]
                ]
            ]
        ];

        $builder = SymfonyMock::getContainerBuilder($this);
        $bundle = new SpipuProcessBundle();
        $extension = $bundle->getContainerExtension();
        $configuration = new Configuration($bundle, $builder, $extension->getAlias());
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testConfigurationMissingSteps()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test',
                ]
            ]
        ];

        $builder = SymfonyMock::getContainerBuilder($this);
        $bundle = new SpipuProcessBundle();
        $extension = $bundle->getContainerExtension();
        $configuration = new Configuration($bundle, $builder, $extension->getAlias());
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testConfigurationEmptySteps()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test',
                    'steps' => [

                    ],
                ]
            ]
        ];

        $builder = SymfonyMock::getContainerBuilder($this);
        $bundle = new SpipuProcessBundle();
        $extension = $bundle->getContainerExtension();
        $configuration = new Configuration($bundle, $builder, $extension->getAlias());
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testConfigurationMissingStepClass()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test',
                    'steps' => [
                        'first' => [
                        ],
                    ],
                ]
            ]
        ];

        $builder = SymfonyMock::getContainerBuilder($this);
        $bundle = new SpipuProcessBundle();
        $extension = $bundle->getContainerExtension();
        $configuration = new Configuration($bundle, $builder, $extension->getAlias());
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testConfigurationOk()
    {
        $expected = SpipuProcessMock::getConfigurationSampleData();

        $configs = [
            0 => $expected
        ];

        $builder = SymfonyMock::getContainerBuilder($this);
        $bundle = new SpipuProcessBundle();
        $extension = $bundle->getContainerExtension();
        $configuration = new Configuration($bundle, $builder, $extension->getAlias());
        $processor = new Processor();

        $result = $processor->processConfiguration($configuration, $configs);

        $this->assertEquals($expected, $result);
    }
}
