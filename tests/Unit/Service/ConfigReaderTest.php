<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ConfigReader::class)]
class ConfigReaderTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @return MockObject|ContainerInterface
     */
    public static function getMockContainer(TestCase $testCase): ContainerInterface
    {
        return SymfonyMock::getContainer(
            $testCase,
            [
                SpipuProcessMock::COUNT_CLASSNAME => SpipuProcessMock::getStepCountProcessor(),
                SpipuProcessMock::ERROR_CLASSNAME => SpipuProcessMock::getStepErrorProcessor(),
            ],
            [
                'spipu_process' => SpipuProcessMock::getConfigurationSampleDataBuilt(),
            ]
        );
    }

    public static function getService(TestCase $testCase): ConfigReader
    {
        return new ConfigReader(static::getMockContainer($testCase));
    }

    public function testGetWorkflowList(): void
    {
        $expected = [];
        foreach (SpipuProcessMock::getConfigurationSampleDataBuilt() as $workflow) {
            $expected[$workflow['code']] = $workflow['name'];
        }

        $configReader = static::getService($this);

        $this->assertEquals($expected, $configReader->getProcessList());
    }

    public function testWorkflowExists(): void
    {
        $configReader = static::getService($this);
        $this->assertTrue($configReader->isProcessExists('test'));
    }

    public function testWorkflowNotExists(): void
    {
        $configReader = static::getService($this);
        $this->assertFalse($configReader->isProcessExists('not_exists'));
    }

    public function testWorkflowDefinitionExists(): void
    {
        $configReader = static::getService($this);
        $result = $configReader->getProcessDefinition('test');

        $this->assertSame(SpipuProcessMock::getConfigurationSampleDataBuilt()['test'], $result);
    }

    public function testWorkflowDefinitionNotExists(): void
    {
        $configReader = static::getService($this);

        $this->expectException(ProcessException::class);
        $configReader->getProcessDefinition('not_exists');
    }

    public function testGetStepClass(): void
    {
        $configReader = static::getService($this);
        $result = $configReader->getStepClassFromClassname(SpipuProcessMock::COUNT_CLASSNAME);
        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $result);
    }
}
