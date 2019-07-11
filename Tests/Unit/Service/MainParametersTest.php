<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\ConfigurationBundle\Tests\SpipuConfigurationMock;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\MainParameters;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class MainParametersTest extends TestCase
{
    public static function getMainParameters(TestCase $testCase)
    {
        return new MainParameters(
            ConfigReaderTest::getMockContainer($testCase),
            SpipuConfigurationMock::getManager($testCase)
        );
    }

    public function testGet()
    {
        $mainParameters = static::getMainParameters($this);

        $this->assertSame(
            SpipuProcessMock::getConfigurationSampleDataBuilt(),
            $mainParameters->get('spipu_process')
        );

        $this->assertSame('foo.bar', $mainParameters->get('configuration(foo.bar)'));

        $this->assertSame(null, $mainParameters->get('configuration(foo.bar) '));

        $this->assertSame(null, $mainParameters->get(' configuration(foo.bar)'));
    }

    public function testSet()
    {
        $mainParameters = static::getMainParameters($this);

        $this->expectException(ProcessException::class);
        $mainParameters->set('key', 'value');
    }

    public function testSetDefaultValue()
    {
        $mainParameters = static::getMainParameters($this);

        $this->expectException(ProcessException::class);
        $mainParameters->setDefaultValue('key', 'value');
    }

    public function testSetParentParameters()
    {
        $parameters = $this->createMock(ParametersInterface::class);

        $mainParameters = static::getMainParameters($this);

        $this->expectException(ProcessException::class);
        $mainParameters->setParentParameters($parameters);
    }
}