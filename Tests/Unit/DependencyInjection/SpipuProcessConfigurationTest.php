<?php
namespace Spipu\ProcessBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\DependencyInjection\SpipuProcessConfiguration;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class SpipuProcessConfigurationTest extends TestCase
{
    public function testMissingWorkflowName()
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

        $configuration = new SpipuProcessConfiguration();
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testMissingSteps()
    {
        $configs = [
            0 => [
                'test' => [
                    'name' => 'Test',
                ]
            ]
        ];

        $configuration = new SpipuProcessConfiguration();
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testEmptySteps()
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

        $configuration = new SpipuProcessConfiguration();
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testMissingStepClass()
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

        $configuration = new SpipuProcessConfiguration();
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);

        $processor->processConfiguration($configuration, $configs);
    }

    public function testOk()
    {
        $expected = SpipuProcessMock::getConfigurationSampleData();

        $configs = [
            0 => $expected
        ];

        $configuration = new SpipuProcessConfiguration();

        $processor = new Processor();
        $result = $processor->processConfiguration($configuration, $configs);



        $this->assertEquals($expected, $result);
    }
}