<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\ProcessBundle\Service\InputsFactory;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(InputsFactory::class)]
class InputsFactoryTest extends TestCase
{
    public static function getService(TestCase $testCase, array $services = []): InputsFactory
    {
        $container = SymfonyMock::getContainer($testCase, $services);

        return new InputsFactory($container);
    }

    public function testService(): void
    {
        $factory = static::getService($this);

        $this->assertInstanceOf(Inputs::class, $factory->create([]));
    }
}
