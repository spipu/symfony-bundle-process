<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Step;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class StepTest extends TestCase
{
    public static function getStep(TestCase $testCase, $code, $class, $parameters = [])
    {
        $step = new Step(
            $code,
            $class,
            ParametersTest::getParameters($testCase, $parameters)
        );

        return $step;
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
            ]
        );

        $this->assertSame('test', $step->getCode());
        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $step->getProcessor());
        $this->assertSame('Bar', $step->getParameters()->get('Foo'));
    }
}