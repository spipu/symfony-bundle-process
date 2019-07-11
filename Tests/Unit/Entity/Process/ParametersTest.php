<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Parameters;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class ParametersTest extends TestCase
{
    public static function getParameters(TestCase $testCase, array $values = [])
    {
        $mainParameters = SpipuProcessMock::getMainParameters($testCase);

        $parameters = new Parameters($values);
        $parameters->setParentParameters($mainParameters);

        return $parameters;
    }

    public function testOk()
    {
        $parameters = static::getParameters(
            $this,
            [
                'param1' => 'Foo',
                'param2' => '{{ param1 }} Bar',
                'array'  => ['{{ param1 }}', '{{ param2 }}', '{{ param3 }}']
            ]
        );

        $parameters->set('param3', 'Ok');

        $this->assertSame('Foo', $parameters->get('param1'));
        $this->assertSame('Foo Bar', $parameters->get('param2'));
        $this->assertSame('Ok', $parameters->get('param3'));

        $this->assertSame(['Foo', 'Foo Bar', 'Ok'], $parameters->get('array'));


        $parameters->setDefaultValue('param3', 'default');
        $this->assertSame('Ok', $parameters->get('param3'));

        $parameters->setDefaultValue('param4', 'default');
        $this->assertSame('default', $parameters->get('param4'));

        $parameters->setDefaultValue('param5', 10);
        $this->assertSame(10, $parameters->get('param5'));
    }
}