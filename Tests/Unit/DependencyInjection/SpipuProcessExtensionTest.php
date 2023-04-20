<?php
namespace Spipu\ProcessBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\DependencyInjection\RolesHierarchyExtensionExtensionInterface;
use Spipu\CoreBundle\Service\RoleDefinitionInterface;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\DependencyInjection\SpipuProcessConfiguration;
use Spipu\ProcessBundle\DependencyInjection\SpipuProcessExtension;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Spipu\UiBundle\Form\Options\YesNo;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class SpipuProcessExtensionTest extends TestCase
{
    public function testBase()
    {
        $builder = SymfonyMock::getContainerBuilder($this);

        $extension = new SpipuProcessExtension();

        $this->assertInstanceOf(ExtensionInterface::class, $extension);

        $this->assertSame('spipu_process', $extension->getAlias());

        $this->assertInstanceOf(RolesHierarchyExtensionExtensionInterface::class, $extension);
        $this->assertInstanceOf(RoleDefinitionInterface::class, $extension->getRolesHierarchy());

        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $extension);
        $this->assertInstanceOf(SpipuProcessConfiguration::class, $extension->getConfiguration([], $builder));

        $this->assertFalse($builder->hasParameter('spipu_process'));
        $extension->load([], $builder);
        $this->assertTrue($builder->hasParameter('spipu_process'));
        $this->assertSame([], $builder->getParameter('spipu_process'));
    }

    public function testLoadOk()
    {
        $configs = [0 => SpipuProcessMock::getConfigurationSampleData()];
        $workflows = SpipuProcessMock::getConfigurationSampleDataBuilt();

        $containerBuilder = SymfonyMock::getContainerBuilder($this);

        $containerBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('spipu_process', $workflows);

        $extension = new SpipuProcessExtension();
        $extension->load($configs, $containerBuilder);
    }

    public function testLoadKoMimeTypeWithoutFile()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test KO 1',
                    'options' => [
                        'can_be_put_in_queue' => false,
                        'can_be_rerun_automatically' => false,
                        'process_lock_on_failed' => true,
                        'process_lock' => [],
                        'needed_role' => null,
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
            ]
        ];

        $containerBuilder = SymfonyMock::getContainerBuilder($this);

        $containerBuilder
            ->expects($this->never())
            ->method('setParameter');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Config Error - allowed_mime_types can be used only with file type');

        $extension = new SpipuProcessExtension();
        $extension->load($configs, $containerBuilder);
    }

    public function testLoadKoRegexpWithoutString()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test KO 1',
                    'options' => [
                        'can_be_put_in_queue' => false,
                        'can_be_rerun_automatically' => false,
                        'process_lock_on_failed' => true,
                        'process_lock' => [],
                        'needed_role' => null,
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
            ]
        ];

        $containerBuilder = SymfonyMock::getContainerBuilder($this);

        $containerBuilder
            ->expects($this->never())
            ->method('setParameter');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Config Error - regexp can be used only with string type');

        $extension = new SpipuProcessExtension();
        $extension->load($configs, $containerBuilder);
    }

    public function testLoadKoOptionWithFile()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test KO 2',
                    'options' => [
                        'can_be_put_in_queue' => false,
                        'can_be_rerun_automatically' => false,
                        'process_lock_on_failed' => true,
                        'process_lock' => [],
                        'needed_role' => null,
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
            ]
        ];

        $containerBuilder = SymfonyMock::getContainerBuilder($this);

        $containerBuilder
            ->expects($this->never())
            ->method('setParameter');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Config Error - options can not be used with file type');

        $extension = new SpipuProcessExtension();
        $extension->load($configs, $containerBuilder);
    }
}
