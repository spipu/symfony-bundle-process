<?php
namespace Spipu\ProcessBundle\Tests\Unit\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Repository\TaskRepository;
use Spipu\ProcessBundle\Service\Status;

class TaskRepositoryTest extends TestCase
{
    public function testRepository()
    {
        $repository = new TaskRepository(
            SymfonyMock::getEntityRegistry($this),
            new Status()
        );

        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }
}
