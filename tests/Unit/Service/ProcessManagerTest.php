<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use DateTime;
use Doctrine\DBAL\Exception\NoKeyValue AS DbalException;
use Doctrine\DBAL\Result as DbalResult;
use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Service\AsynchronousCommand;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\FileManagerInterface;
use Spipu\ProcessBundle\Service\Logger;
use Spipu\ProcessBundle\Service\LoggerOutput;
use Spipu\ProcessBundle\Service\ProcessManager;
use Spipu\ProcessBundle\Service\ReportManager;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Spipu\ProcessBundle\Tests\Unit\Entity\Process\ProcessTest;

class ProcessManagerTest extends TestCase
{
    public static function getService(TestCase $testCase, bool $toExecuteAsynchronously = false)
    {
        $configReader = ConfigReaderTest::getService($testCase);
        $mainParameters = MainParametersTest::getMainParameters($testCase);

        $entityManager = SymfonyMock::getEntityManager($testCase);
        $entityManager
            ->method('persist')
            ->willReturnCallback(
                function ($model) {
                    $refObject = new \ReflectionObject($model);
                    $refProperty = $refObject->getProperty('id');
                    $refProperty->setAccessible(true);
                    $refProperty->setValue($model, 1);
                }
            );

        $asynchronousCommand = $testCase->createMock(AsynchronousCommand::class);
        if ($toExecuteAsynchronously) {
            $asynchronousCommand
                ->expects($testCase->once())
                ->method('execute')
                ->with('spipu:process:rerun', [1])
                ->willReturn(true);
        }

        $reportManager = $testCase->createMock(ReportManager::class);
        $fileManager = $testCase->createMock(FileManagerInterface::class);

        $moduleConfiguration = ModuleConfigurationTest::getService($testCase);

        /** @var Logger $logger */
        /** @var AsynchronousCommand $asynchronousCommand */
        /** @var ReportManager $reportManager */

        return new ProcessManager(
            $configReader,
            $mainParameters,
            LoggerTest::getService($testCase),
            $entityManager,
            $asynchronousCommand,
            InputsFactoryTest::getService($testCase),
            $reportManager,
            $moduleConfiguration,
            $fileManager
        );
    }

    public function testConfigReader()
    {
        $manager = static::getService($this);
        $this->assertTrue($manager->getConfigReader()->isProcessExists('test'));
    }

    public function testInitNotExists()
    {
        $manager = static::getService($this);

        $this->assertFalse($manager->getConfigReader()->isProcessExists('not_exists'));

        $this->expectException(ProcessException::class);
        $manager->load('not_exists');
    }

    public function testInitOk()
    {
        $manager = static::getService($this);
        $process = $manager->load('test');

        $process->getInputs()->set('input1', 'string');
        $process->getInputs()->set('input2', 1);
        $process->getInputs()->set('input3', 1.);
        $process->getInputs()->set('input4', true);
        $process->getInputs()->set('input5', ['ok']);

        $this->assertSame('test', $process->getCode());
        $this->assertSame('Test', $process->getName());
        $this->assertSame('Foo', $process->getParameters()->get('param1'));
        $this->assertSame('Foo Bar', $process->getParameters()->get('param2'));

        $this->assertSame(['first', 'second'], array_keys($process->getSteps()));

        $this->assertSame('first', $process->getSteps()['first']->getCode());
        $this->assertSame('second', $process->getSteps()['second']->getCode());

        $this->assertSame('Foo Bar first', $process->getSteps()['first']->getParameters()->get('string'));
        $this->assertSame('Foo Bar second', $process->getSteps()['second']->getParameters()->get('string'));

        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $process->getSteps()['first']->getProcessor());
        $this->assertInstanceOf(SpipuProcessMock::COUNT_CLASSNAME, $process->getSteps()['second']->getProcessor());

        $this->assertSame(1, count($process->getSteps()['first']->getParameters()->get('array')));
        $this->assertSame(3, count($process->getSteps()['second']->getParameters()->get('array')));
    }

    public function testExecuteInputMissing()
    {
        $process = ProcessTest::getProcess($this);

        $manager = static::getService($this);

        $this->expectException(InputException::class);
        $manager->execute($process);
    }

    public function testExecuteOk()
    {
        $process = ProcessTest::getProcess($this);
        $process->getInputs()->set('name', 'Bar');

        $manager = static::getService($this);

        $this->assertNull($process->getLogId());

        $manager->execute(
            $process,
            function (Process $process) {
                $this->assertNotNull($process->getLogId());
            }
        );

        $this->assertSame(1, $process->getParameters()->get('result.first'));
        $this->assertSame(3, $process->getParameters()->get('result.second'));
    }

    public function testExecuteWithGenericException()
    {
        $manager = static::getService($this);
        $process = $manager->load('other');
        $process->getInputs()->set('generic_exception', true);

        $this->assertNotEmpty($process->getTask());
        $this->assertInstanceOf(Task::class, $process->getTask());
        $this->assertSame(0, $process->getTask()->getTryNumber());
        $this->assertSame(Status::CREATED, $process->getTask()->getStatus());
        $this->assertSame(false, $process->getTask()->getCanBeRerunAutomatically());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The Generic Error !');
        try {
            $manager->execute($process);
        } catch (\Exception $e) {
            $this->assertSame(1, $process->getTask()->getTryNumber());
            $this->assertSame(Status::FAILED, $process->getTask()->getStatus());
            $this->assertSame(false, $process->getTask()->getCanBeRerunAutomatically());
            throw $e;
        }
    }

    public function testExecuteWithCallRestException()
    {
        $manager = static::getService($this);
        $process = $manager->load('other');
        $process->getInputs()->set('generic_exception', false);

        $this->assertNotEmpty($process->getTask());
        $this->assertInstanceOf(Task::class, $process->getTask());
        $this->assertSame(0, $process->getTask()->getTryNumber());
        $this->assertSame(Status::CREATED, $process->getTask()->getStatus());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The CallRest Error !');
        try {
            $manager->execute($process);
        } catch (\Exception $e) {
            $this->assertSame(true, $process->getTask()->getCanBeRerunAutomatically());
            $this->assertSame(1, $process->getTask()->getTryNumber());
            $this->assertSame(Status::FAILED, $process->getTask()->getStatus());
            throw $e;
        }
    }

    public function testLoadFromTaskOk()
    {
        $manager = static::getService($this);

        $task = SpipuProcessMock::getTaskEntity(1);
        $task->setCode('test');
        $task->setInputs(json_encode(['input1' => 'to restore']));
        $task->setTryNumber(0);
        $task->setStatus(Status::CREATED);

        $process = $manager->loadFromTask($task);
        $this->assertSame('to restore', $process->getInputs()->get('input1'));
        $this->assertSame($task, $process->getTask());
        $this->assertSame(0, $task->getTryNumber());
        $this->assertSame(Status::CREATED, $task->getStatus());
    }

    public function testLoadFromTaskBadJsonInput()
    {
        $manager = static::getService($this);

        $task = SpipuProcessMock::getTaskEntity(1);
        $task->setCode('test');
        $task->setInputs('');
        $task->setTryNumber(0);
        $task->setStatus(Status::CREATED);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('Invalid Inputs Data');
        try {
            $manager->loadFromTask($task);
        } catch (InputException $e) {
            $this->assertSame(1, $task->getTryNumber());
            $this->assertSame(Status::FAILED, $task->getStatus());

            throw $e;
        }
    }

    public function testLoadFromTaskBadKeyInput()
    {
        $manager = static::getService($this);

        $task = SpipuProcessMock::getTaskEntity(1);
        $task->setCode('test');
        $task->setInputs(json_encode(['input_wrong' => 'to restore']));
        $task->setTryNumber(0);
        $task->setStatus(Status::CREATED);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('input is not authorized');
        try {
            $manager->loadFromTask($task);
        } catch (InputException $e) {
            $this->assertSame(1, $task->getTryNumber());
            $this->assertSame(Status::FAILED, $task->getStatus());

            throw $e;
        }
    }

    public function testExecuteAsynchronousNotAuthorized()
    {
        $manager = static::getService($this);

        $process = $manager->load('test');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('This process can not be executed asynchronously');
        $manager->executeAsynchronously($process);
    }

    public function testExecuteAsynchronousAuthorized()
    {
        $manager = static::getService($this, true);

        $process = $manager->load('other');
        $process->getInputs()->set('generic_exception', true);

        $taskId = $manager->executeAsynchronously($process);

        $this->assertSame(1, $taskId);
    }

    public function testScheduleExecutionNotAuthorized()
    {
        $manager = static::getService($this);

        $process = $manager->load('test');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('This process can not be scheduled, because it can not be put in queue');
        $manager->scheduleExecution($process, new DateTime());
    }

    public function testScheduleExecutionAuthorized()
    {
        $manager = static::getService($this);

        $process = $manager->load('other');
        $process->getInputs()->set('generic_exception', true);

        $expectedDate = new DateTime();
        $taskId = $manager->scheduleExecution($process, $expectedDate);

        $this->assertSame(1, $taskId);
        $this->assertSame(1, $process->getTask()->getId());
        $this->assertSame('created', $process->getTask()->getStatus());
        $this->assertSame($expectedDate, $process->getTask()->getScheduledAt());
    }

    public function testProcessLockWithoutTask()
    {
        $manager = static::getService($this);

        $refObject = new \ReflectionObject($manager);
        $refProperty = $refObject->getProperty('entityManager');
        $refProperty->setAccessible(true);
        $entityManager = $refProperty->getValue($manager);
        $connection = $entityManager->getConnection();

        $expectedQuery =
            "SELECT `id` FROM `spipu_process_task` " .
            "WHERE `code` IN ('lock','other') " .
            "AND ((`status` = 'created' AND `scheduled_at` IS NULL) OR (`status` = 'running') OR (`status` = 'failed')) ".
            "ORDER BY `id` ASC LIMIT 1";

        $fakeResult = $this->createMock(DbalResult::class);
        $fakeResult
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with($expectedQuery)
            ->willReturn($fakeResult);

        $process = $manager->load('lock');
        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('This process can not be executed, because it is locked by another one');
        $manager->execute($process);
    }

    public function testProcessLockWithTask()
    {
        $manager = static::getService($this);

        $refObject = new \ReflectionObject($manager);
        $refProperty = $refObject->getProperty('entityManager');
        $refProperty->setAccessible(true);
        $entityManager = $refProperty->getValue($manager);
        $connection = $entityManager->getConnection();

        $expectedQuery =
            "SELECT `id` FROM `spipu_process_task` " .
            "WHERE `code` IN ('lock','other') " .
            "AND ((`status` = 'created' AND `scheduled_at` IS NULL AND id < 42) OR (`status` = 'running') OR (`status` = 'failed' AND id <> 42)) ".
            "ORDER BY `id` ASC LIMIT 1";

        $fakeResult = $this->createMock(DbalResult::class);
        $fakeResult
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with($expectedQuery)
            ->willReturn($fakeResult);

        $task = new Task();
        $task->setCode('lock');
        $task->setInputs(json_encode([]));
        $refObject = new \ReflectionObject($task);
        $refProperty = $refObject->getProperty('id');
        $refProperty->setAccessible(true);
        $refProperty->setValue($task, 42);

        $process = $manager->loadFromTask($task);
        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('This process can not be executed, because it is locked by another one');
        $manager->execute($process);
    }

    public function testProcessAsynchronousLock()
    {
        $manager = static::getService($this);

        $refObject = new \ReflectionObject($manager);
        $refProperty = $refObject->getProperty('entityManager');
        $refProperty->setAccessible(true);
        $entityManager = $refProperty->getValue($manager);
        $connection = $entityManager->getConnection();

        $expectedQuery =
            "SELECT `id` FROM `spipu_process_task` " .
            "WHERE `code` IN ('lock','other') " .
            "AND ((`status` = 'created' AND `scheduled_at` IS NULL) OR (`status` = 'running') OR (`status` = 'failed')) ".
            "ORDER BY `id` ASC LIMIT 1";

        $fakeResult = $this->createMock(DbalResult::class);
        $fakeResult
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with($expectedQuery)
            ->willReturn($fakeResult);

        $process = $manager->load('lock');
        $taskId = $manager->executeAsynchronously($process);
        $this->assertSame(1, $taskId);
        $this->assertSame(1, $process->getTask()->getId());
        $this->assertSame('created', $process->getTask()->getStatus());
        $this->assertNotNull($process->getTask()->getScheduledAt());
    }

    public function testProcessLockWithoutTaskWithErrorOnQuery()
    {
        $manager = static::getService($this);

        $refObject = new \ReflectionObject($manager);
        $refProperty = $refObject->getProperty('entityManager');
        $refProperty->setAccessible(true);
        $entityManager = $refProperty->getValue($manager);
        $connection = $entityManager->getConnection();

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new DbalException('Fake exception'));

        $process = $manager->load('lock');
        $this->assertSame(3, $manager->execute($process));
        $this->assertSame(3, $process->getParameters()->get('result.count'));
    }

    public function testProcessWithOutput()
    {
        $output = SymfonyMock::getConsoleOutput($this);
        $loggerOutput = new LoggerOutput($output);

        $manager = static::getService($this);
        $manager->setLoggerOutput($loggerOutput);

        $process = $manager->load('lock');
        $manager->execute($process);
        $this->assertSame(3, $manager->execute($process));
        $this->assertSame(3, $process->getParameters()->get('result.count'));

        $consoleOutputResult = SymfonyMock::getConsoleOutputResult();
        $this->assertSame(18, count($consoleOutputResult));
    }
}
