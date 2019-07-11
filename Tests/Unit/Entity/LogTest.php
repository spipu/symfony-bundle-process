<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class LogTest extends TestCase
{
    public function testEntity()
    {
        $task = SpipuProcessMock::getTaskEntity();

        $entity = SpipuProcessMock::getLogEntity(1);
        $this->assertSame(1, $entity->getId());

        $entity->setCode('code');
        $entity->setContent('content');
        $entity->setStatus(Status::FAILED);
        $entity->setProgress(42);
        $entity->setTask($task);

        $this->assertSame('code', $entity->getCode());
        $this->assertSame('content', $entity->getContent());
        $this->assertSame(Status::FAILED, $entity->getStatus());
        $this->assertSame(42, $entity->getProgress());
        $this->assertSame($task, $entity->getTask());
    }
}
