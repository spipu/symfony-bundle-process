<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use Doctrine\DBAL\Exception AS DbalException;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Entity\Log as ProcessLog;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\Logger;
use Spipu\ProcessBundle\Service\LoggerOutput;
use Spipu\ProcessBundle\Service\MailManager;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Throwable;

class LoggerTest extends TestCase
{
    public static function getService(TestCase $testCase, ?MailManager $mailManager = null): Logger
    {
        $entityManager = SymfonyMock::getEntityManager($testCase);
        $entityManager
            ->method('persist')
            ->willReturnCallback(
                function ($model) {
                    $refObject = new ReflectionObject($model);
                    $refProperty = $refObject->getProperty('id');
                    $refProperty->setAccessible(true);
                    $refProperty->setValue($model, 1);
                }
            );

        return new Logger($entityManager, $mailManager);
    }

    public function testNotInit(): void
    {
        $logger = static::getService($this);

        $this->expectException(ProcessException::class);
        $logger->debug('test');
    }

    public function testErrorOnFlush(): void
    {
        $expectedException = new DbalException('Fake DBAL Exception');

        $entityManager = SymfonyMock::getEntityManager($this);
        $entityManager
            ->method('persist')
            ->willReturnCallback(
                function ($model) {
                    $refObject = new ReflectionObject($model);
                    $refProperty = $refObject->getProperty('id');
                    $refProperty->setAccessible(true);
                    $refProperty->setValue($model, 1);
                }
            );

        $entityManager
            ->method('flush')
            ->willThrowException($expectedException);

        $logger = new Logger($entityManager, null);


        $foundException = null;
        try {
            ob_start();
            $logger->init('test', 1, null);
        } catch (Throwable $e) {
            $foundException = $e;
        } finally {
            $foundResult = explode("\n", trim(ob_get_clean()));
        }

        $expectedResult = [
            'FATAL ERROR DURING ENTITY MANAGER FLUSH!!!',
            'Log Content',
            '============================',
            'Array',
            '(',
            '    [0] => Array',
            '        (',
            '            [date] => xxxx',
            '            [memory] => xxxx',
            '            [memory_peak] => xxxx',
            '            [level] => info',
            '            [message] => Process Started [test]',
            '        )',
            '',
            ')',
            '============================',
        ];
        $this->assertSame($expectedException, $foundException);
        $this->assertSame(count($expectedResult), count($foundResult));

        unset($expectedResult[7], $expectedResult[8], $expectedResult[9]);
        unset($foundResult[7], $foundResult[8], $foundResult[9]);

        $this->assertSame($expectedResult, $foundResult);
    }

    public function testOk(): void
    {
        $logger = static::getService($this);

        $logger->init('test', 1, null);
        $logger->debug('message 1');
        $logger->info('message 2');
        $logger->notice('message 3');
        $logger->warning('message 4');
        $logger->error('message 5');
        $logger->critical('message 6');
        $logger->alert('message 7');
        $logger->emergency('message 8');
        $logger->finish(Status::FINISHED);

        $model = $logger->getModel();
        $this->assertSame('test', $model->getCode());
        $this->assertSame(Status::FINISHED, $model->getStatus());

        $messages = json_decode($model->getContent(), true);
        $this->assertTrue(is_array($messages));

        $this->assertSame('debug', $messages[1]['level']);
        $this->assertSame('info', $messages[2]['level']);
        $this->assertSame('notice', $messages[3]['level']);
        $this->assertSame('warning', $messages[4]['level']);
        $this->assertSame('error', $messages[5]['level']);
        $this->assertSame('critical', $messages[6]['level']);
        $this->assertSame('alert', $messages[7]['level']);
        $this->assertSame('emergency', $messages[8]['level']);

        $this->assertSame('message 1', $messages[1]['message']);
        $this->assertSame('message 2', $messages[2]['message']);
        $this->assertSame('message 3', $messages[3]['message']);
        $this->assertSame('message 4', $messages[4]['message']);
        $this->assertSame('message 5', $messages[5]['message']);
        $this->assertSame('message 6', $messages[6]['message']);
        $this->assertSame('message 7', $messages[7]['message']);
        $this->assertSame('message 8', $messages[8]['message']);

        $this->assertNull((clone $logger)->getModel());
    }

    public function testProgress(): void
    {
        $logger = static::getService($this);

        $task = SpipuProcessMock::getTaskEntity(42);
        $logger->init('test', 2, $task);
        $this->assertSame($task, $logger->getModel()->getTask());

        $logger->setCurrentStep(0, true);
        $this->assertSame(0, $logger->getModel()->getProgress());
        $this->assertSame(0, $task->getProgress());

        $logger->setProgress(50);
        $this->assertSame(00, $logger->getModel()->getProgress());
        $this->assertSame(00, $task->getProgress());

        $logger->setCurrentStep(0, false);
        $this->assertSame(0, $logger->getModel()->getProgress());
        $this->assertSame(0, $task->getProgress());

        $logger->setProgress(50);
        $this->assertSame(25, $logger->getModel()->getProgress());
        $this->assertSame(25, $task->getProgress());

        $logger->setProgress(98);
        $this->assertSame(49, $logger->getModel()->getProgress());
        $this->assertSame(49, $task->getProgress());

        $logger->setCurrentStep(1, false);
        $this->assertSame(50, $logger->getModel()->getProgress());
        $this->assertSame(50, $task->getProgress());

        $logger->setProgress(50);
        $this->assertSame(75, $logger->getModel()->getProgress());
        $this->assertSame(75, $task->getProgress());

        $logger->setProgress(98);
        $this->assertSame(99, $logger->getModel()->getProgress());
        $this->assertSame(99, $task->getProgress());
    }

    public function testFinishWithFailed(): void
    {
        $lastException = new ProcessException('My foo exception');

        $mailManager = $this->createMock(MailManager::class);
        $mailManager
            ->expects($this->once())
            ->method('sendAlert')
            ->willReturnCallback(
                function (ProcessLog $processLog, ?Throwable $exception = null) use ($lastException) {
                    $this->assertInstanceOf(ProcessException::class, $exception);
                    $this->assertSame($lastException, $exception);

                    throw new ProcessException('mock error');
                }
            );

        /** @var MailManager $mailManager */
        $logger = static::getService($this, $mailManager);
        $logger->init('test', 1, null);
        $logger->setLastException($lastException);
        $logger->finish(Status::FAILED);

        $model = $logger->getModel();
        $this->assertSame('test', $model->getCode());
        $this->assertSame(Status::FAILED, $model->getStatus());

        $messages = json_decode($model->getContent(), true);
        $this->assertTrue(is_array($messages));

        $this->assertSame('warning', $messages[1]['level']);
        $this->assertSame('critical', $messages[2]['level']);
        $this->assertSame('critical', $messages[3]['level']);

        $this->assertSame('A technical alert email will been sent', $messages[1]['message']);
        $this->assertSame(' => ERROR when sending the email', $messages[2]['message']);
        $this->assertStringContainsString('mock error', $messages[3]['message']);
    }

    public function testOutput()
    {
        $output = SymfonyMock::getConsoleOutput($this);
        $loggerOutput = new LoggerOutput($output);

        $logger = static::getService($this);
        $logger->setLoggerOutput($loggerOutput);

        $logger->init('test', 1, null);
        $logger->debug('test of output');
        $logger->debug('end of test');

        $outputMessages = [];
        $outputRegexp = '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}]\[[ 0-9.]+ Mo]\[[ 0-9.]+ Mo](.*)$/';
        foreach (SymfonyMock::getConsoleOutputResult() as $outputMessage) {
            if (preg_match($outputRegexp, $outputMessage, $match)) {
                $outputMessages[] = $match[1];
            }
            $this->assertMatchesRegularExpression($outputRegexp, $outputMessage);
        }

        $this->assertSame(
            [
                "[info___] Process Started [test]",
                "[debug__] test of output",
                "[debug__] end of test",
            ],
            $outputMessages
        );
    }

    public function testInitFromLog()
    {
        $logger = static::getService($this);

        $log = new ProcessLog();
        $logger->initFromExistingLog($log);
        $this->assertSame([], $logger->getMessages());
        $this->assertSame($log, $logger->getModel());

        $log = new ProcessLog();
        $log->setContent(json_encode([]));
        $logger->initFromExistingLog($log);
        $this->assertSame([], $logger->getMessages());
        $this->assertSame($log, $logger->getModel());

        $log = new ProcessLog();
        $log->setContent(json_encode('string'));
        $logger->initFromExistingLog($log);
        $this->assertSame([], $logger->getMessages());

        $log = new ProcessLog();
        $log->setContent('bad json');
        $logger->initFromExistingLog($log);
        $this->assertSame([], $logger->getMessages());


        $messages = ['my foo', 'my bar'];
        $log = new ProcessLog();
        $log->setContent(json_encode($messages));
        $logger->initFromExistingLog($log);
        $this->assertSame($messages, $logger->getMessages());
    }
}
