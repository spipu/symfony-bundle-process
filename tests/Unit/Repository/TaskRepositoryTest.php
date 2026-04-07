<?php

declare(strict_types=1);

namespace Spipu\ProcessBundle\Tests\Unit\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Repository\TaskRepository;
use Spipu\ProcessBundle\Service\Status;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(TaskRepository::class)]
class TaskRepositoryTest extends TestCase
{
    public function testRepository(): void
    {
        $repository = new TaskRepository(
            SymfonyMock::getEntityRegistry($this),
            new Status()
        );

        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }
}
