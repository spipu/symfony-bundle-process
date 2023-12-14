<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\ProcessBundle\Service\InputsFactory;

class InputsFactoryTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @param array $services
     * @return InputsFactory
     */
    public static function getService(TestCase $testCase, array $services = [])
    {
        $container = SymfonyMock::getContainer($testCase, $services);

        return new InputsFactory($container);
    }

    public function testService()
    {
        $factory = static::getService($this);

        $this->assertInstanceOf(Inputs::class, $factory->create([]));
    }
}
