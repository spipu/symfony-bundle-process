<?php
namespace Spipu\ProcessBundle\Tests\Unit\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Repository\LogRepository;
use Spipu\ProcessBundle\Service\Status;

class LogRepositoryTest extends TestCase
{
    public function testRepository()
    {
        $repository = new LogRepository(
            SymfonyMock::getEntityRegistry($this),
            new Status()
        );

        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }
}
