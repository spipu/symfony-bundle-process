<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Service\TaskManager;

class TaskManagerTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @return TaskManager
     */
    static public function getService(TestCase $testCase): TaskManager
    {
        return new TaskManager(
            StatusTest::getService($testCase),
            SymfonyMock::getEntityManager($testCase)
        );
    }

    public function testRunning(): void
    {
        $service = self::getService($this);

        $task = new Task();
        $task->setPidValue(null);
        $this->assertFalse($service->isPidRunning($task));

        $task = new Task();
        $task->setPidValue(0);
        $this->assertFalse($service->isPidRunning($task));

        $task = new Task();
        $task->setPidValue(12356789);
        $this->assertFalse($service->isPidRunning($task));

        $task = new Task();
        $task->setPidValue(getmypid());
        $this->assertTrue($service->isPidRunning($task));
    }

    public function testKillingKo(): void
    {
        $task = new Task();
        $task->setStatus(Status::CREATED);
        $task->setPidValue(null);

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('spipu.process.error.kill');

        $service = self::getService($this);
        $service->kill($task, 'Foo Bar');
    }

    public function testKillingOkNoPid(): void
    {
        $task = new Task();
        $task->setStatus(Status::RUNNING);
        $task->setPidValue(null);
        $task->setTryNumber(0);

        $service = self::getService($this);
        $service->kill($task, 'Foo Bar');

        $this->assertSame(Status::FAILED, $task->getStatus());
        $this->assertSame('Foo Bar', $task->getTryLastMessage());
        $this->assertNotNull($task->getTryLastAt());
        $this->assertSame(1, $task->getTryNumber());
        $this->assertFalse($task->getCanBeRerunAutomatically());
    }

    public function testKillingOkPid(): void
    {
        $command = 'sleep 2 > /dev/null 2>&1 & echo $!';
        exec($command, $output);

        $task = new Task();
        $task->setStatus(Status::RUNNING);
        $task->setPidValue((int) $output[0]);
        $task->setTryNumber(0);

        $service = self::getService($this);
        $service->kill($task, 'Foo Bar');

        $this->assertSame(Status::FAILED, $task->getStatus());
        $this->assertSame('Foo Bar', $task->getTryLastMessage());
        $this->assertNotNull($task->getTryLastAt());
        $this->assertSame(1, $task->getTryNumber());
        $this->assertFalse($task->getCanBeRerunAutomatically());
    }

    public function testKillingKoPid(): void
    {
        $this->assertNotSame('root', strtolower(get_current_user()));

        $task = new Task();
        $task->setStatus(Status::RUNNING);
        $task->setPidValue(1);
        $task->setTryNumber(0);

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Error during kill - Error #1 - Operation not permitted');

        $service = self::getService($this);
        $service->kill($task, 'Foo Bar');
    }
}
