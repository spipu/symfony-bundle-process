<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Step;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class StepTest extends TestCase
{
    public static function getStep(TestCase $testCase, $code, $class, $parameters = [], $ignoreInProgress = false)
    {
        return new Step(
            $code,
            $class,
            ParametersTest::getParameters($testCase, $parameters),
            $ignoreInProgress
        );
    }

    public function testOk()
    {
        $step = static::getStep(
            $this,
            'test',
            SpipuProcessMock::getStepCountProcessor(),
            [
                'Foo' => 'Bar',
                'array' => [1,2,3]
            ],
            false
        );

        $this->assertSame('test', $step->getCode());
        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $step->getProcessor());
        $this->assertSame('Bar', $step->getParameters()->get('Foo'));
        $this->assertFalse($step->isIgnoreInProgress());

        $step = static::getStep(
            $this,
            'test',
            SpipuProcessMock::getStepCountProcessor(),
            [],
            true
        );
        $this->assertTrue($step->isIgnoreInProgress());
    }
}