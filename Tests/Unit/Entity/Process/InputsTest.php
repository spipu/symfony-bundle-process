<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\ProcessBundle\Exception\InputException;

class InputsTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @param array $description
     * @return Inputs
     */
    public static function getInputs(TestCase $testCase, array $description = [])
    {
        $inputs = new Inputs($description);

        return $inputs;
    }

    public function testInvalidType()
    {
        $this->expectException(InputException::class);
        self::getInputs($this, ['input' => ['type' => 'wrong']]);
    }

    public function testInvalidSetterKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->set('wrong', 'value');
    }

    public function testInvalidGetterKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);
        $input->set('input', 'value');

        $this->expectException(InputException::class);
        $input->get('wrong');
    }

    public function testDefinition()
    {
        $definitions = ['input' => ['type' => 'string']];
        $inputs = self::getInputs($this, $definitions);
        $inputs->getInput('input')->getType();

        $this->assertSame($definitions['input']['type'], $inputs->getInput('input')->getType());
    }

    public function testStringNotSetKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testStringWrongType()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->set('input', 10);
    }

    public function testStringOk()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);
        $input->set('input', 'value');
        $this->assertSame('value', $input->get('input'));
    }

    public function testIntNotSetKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'int']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testIntWrongType()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'int']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testIntOk()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'int']]);
        $input->set('input', 10);
        $this->assertSame(10, $input->get('input'));
    }

    public function testFloatNotSetKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'float']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testFloatWrongType()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'float']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testFloatOk()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'float']]);
        $input->set('input', 10.);
        $this->assertSame(10., $input->get('input'));

        $input->set('input', 9);
        $this->assertSame(9., $input->get('input'));
    }

    public function testBoolNotSetKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'bool']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testBoolWrongType()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'bool']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testBoolOk()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'bool']]);
        $input->set('input', true);
        $this->assertSame(true, $input->get('input'));
    }

    public function testArrayNotSetKey()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testArrayWrongType()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testArrayOk()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array']]);
        $input->set('input', ['value']);
        $this->assertSame(['value'], $input->get('input'));
    }

    public function testValidateKo()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->validate();
    }

    public function testValidateOk()
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);
        $input->set('input', 'value');
        $this->assertTrue($input->validate());

        $this->assertSame(['input' => 'value'], $input->getAll());
    }
}