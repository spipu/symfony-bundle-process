<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\Logger;
use Spipu\ProcessBundle\Service\MailManager;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class LoggerTest extends TestCase
{
    public static function getService(TestCase $testCase, ?MailManager $mailManager = null)
    {
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

        return new Logger($entityManager, $mailManager);
    }

    public function testNotInit()
    {
        $logger = static::getService($this);

        $this->expectException(ProcessException::class);
        $logger->debug('test');
    }

    public function testOk()
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

    public function testProgress()
    {
        $logger = static::getService($this);

        $task = SpipuProcessMock::getTaskEntity(42);
        $logger->init('test', 2, $task);
        $this->assertSame($task, $logger->getModel()->getTask());

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


    public function testFinishWithFailed()
    {
        $mailManager = $this->createMock(MailManager::class);
        $mailManager
            ->expects($this->once())
            ->method('sendAlert')
            ->willThrowException(new ProcessException('mock error'));

        /** @var MailManager $mailManager */
        $logger = static::getService($this, $mailManager);
        $logger->init('test', 1, null);
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
}