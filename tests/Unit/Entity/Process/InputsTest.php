<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Tests\Unit\Service\InputsFactoryTest;
use Spipu\UiBundle\Tests\OptionIntegerMock;

class InputsTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @param array $description
     * @return Inputs
     */
    public static function getInputs(TestCase $testCase, array $description = [])
    {
        foreach($description as $name => &$config) {
            $config['name'] = $name;
            if (!array_key_exists('allowed_mime_types', $config)) {
                $config['allowed_mime_types'] = [];
            }
            if (!array_key_exists('required', $config)) {
                $config['required'] = true;
            }
            if (!array_key_exists('regexp', $config)) {
                $config['regexp'] = null;
            }
            if (!array_key_exists('help', $config)) {
                $config['help'] = null;
            }
        }

        $services = [
            'optionsMock' => new OptionIntegerMock(),
        ];

        return InputsFactoryTest::getService($testCase, $services)->create($description);
    }

    public function testInvalidType(): void
    {
        $this->expectException(InputException::class);
        self::getInputs($this, ['input' => ['type' => 'wrong']]);
    }

    public function testInvalidSetterKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->set('wrong', 'value');
    }

    public function testInvalidGetterKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);
        $input->set('input', 'value');

        $this->expectException(InputException::class);
        $input->get('wrong');
    }

    public function testDefinition(): void
    {
        $definitions = ['input' => ['type' => 'string']];
        $inputs = self::getInputs($this, $definitions);
        $inputs->getInput('input')->getType();

        $this->assertSame($definitions['input']['type'], $inputs->getInput('input')->getType());
    }

    public function testStringNotSetKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testStringWrongType(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->set('input', 10);
    }

    public function testStringOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);
        $input->set('input', 'value');
        $this->assertSame('value', $input->get('input'));
    }

    public function testIntNotSetKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'int']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testIntWrongType(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'int']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testIntOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'int']]);
        $input->set('input', 10);
        $this->assertSame(10, $input->get('input'));
        $this->assertSame(null, $input->getInput('input')->getOptions());
        $this->assertSame([], $input->getInput('input')->getAllowedMimeTypes());
    }

    public function testFloatNotSetKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'float']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testFloatWrongType(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'float']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testFloatOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'float']]);
        $input->set('input', 10.);
        $this->assertSame(10., $input->get('input'));

        $input->set('input', 9);
        $this->assertSame(9., $input->get('input'));
    }

    public function testBoolNotSetKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'bool']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testBoolWrongType(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'bool']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testBoolOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'bool']]);
        $input->set('input', true);
        $this->assertSame(true, $input->get('input'));
    }

    public function testArrayNotSetKey(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array']]);

        $this->expectException(InputException::class);
        $input->get('input');
    }

    public function testArrayWrongType(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array']]);

        $this->expectException(InputException::class);
        $input->set('input', '10');
    }

    public function testArrayOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array']]);
        $input->set('input', ['value']);
        $this->assertSame(['value'], $input->get('input'));
    }

    public function testValidateKo(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);

        $this->expectException(InputException::class);
        $input->validate();
    }

    public function testValidateOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string']]);
        $input->set('input', 'value');
        $this->assertTrue($input->validate());

        $this->assertSame(['input' => 'value'], $input->getAll());
    }

    public function testRequiredStringOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string', 'required' => true]]);
        $this->assertTrue($input->getInput('input')->isRequired());

        $input = self::getInputs($this, ['input' => ['type' => 'string', 'required' => false]]);
        $this->assertFalse($input->getInput('input')->isRequired());

        $input->set('input', '');
        $this->assertTrue($input->validate());
        $this->assertNull($input->get('input'));

        $input->set('input', null);
        $this->assertTrue($input->validate());
        $this->assertNull($input->get('input'));
    }

    public function testRequiredStringKo(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string', 'required' => true]]);
        $this->assertTrue($input->getInput('input')->isRequired());

        $this->expectException(InputException::class);
        $input->set('input', '');
    }

    public function testRequiredArrayOk(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'array', 'required' => true]]);
        $this->assertTrue($input->getInput('input')->isRequired());

        $input = self::getInputs($this, ['input' => ['type' => 'array', 'required' => false]]);
        $this->assertFalse($input->getInput('input')->isRequired());

        $input->set('input', []);
        $this->assertTrue($input->validate());
        $this->assertSame([], $input->get('input'));

        $input->set('input', '');
        $this->assertTrue($input->validate());
        $this->assertSame([], $input->get('input'));

        $input->set('input', null);
        $this->assertTrue($input->validate());
        $this->assertSame([], $input->get('input'));
    }

    public function testRequiredArrayKo(): void
    {
        $input = self::getInputs($this, ['input' => ['type' => 'string', 'required' => true]]);
        $this->assertTrue($input->getInput('input')->isRequired());

        $this->expectException(InputException::class);
        $input->set('input', []);
    }

    public function testOptionsOk(): void
    {
        $input = self::getInputs(
            $this,
            [
                'input' => [
                    'type'    => 'int',
                    'options' => 'optionsMock',
                ]
            ]
        );

        $this->assertInstanceOf(OptionIntegerMock::class, $input->getInput('input')->getOptions());

        $input->set('input', 0);
        $this->assertSame(0, $input->get('input'));

        $input->set('input', 1);
        $this->assertSame(1, $input->get('input'));

        $this->expectException(InputException::class);
        $input->set('input', 2);
    }

    public function testFileOk(): void
    {
        $input = self::getInputs(
            $this,
            [
                'input' => [
                    'type'    => 'file',
                    'allowed_mime_types' => ['jpg', 'png'],
                ]
            ]
        );

        $this->assertSame('file', $input->getInput('input')->getType());
        $this->assertSame(['jpg', 'png'], $input->getInput('input')->getAllowedMimeTypes());
    }
}
