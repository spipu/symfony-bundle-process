<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\Tests\SpipuCoreMock;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Service\MailManager;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Spipu\UiBundle\Event\FormDefinitionEvent;

class MailManagerTest extends TestCase
{
    public function testServiceDisable()
    {
        $configuration = ModuleConfigurationTest::getService($this, ['process.failed.send_email' => false]);
        $url = UrlTest::getService($this);
        $mailManager = SpipuCoreMock::getMailManager($this);
        $eventDispatcher = SymfonyMock::getEventDispatcher($this);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->anything(), 'spipu.process.log.failed');

        $service = new MailManager($configuration, $mailManager, $url, $eventDispatcher);

        $log = SpipuProcessMock::getLogEntity(1);

        $this->assertSame(false, $service->sendAlert($log));
    }

    public function testServiceEnable()
    {
        $configuration = ModuleConfigurationTest::getService($this, ['process.failed.send_email' => true]);
        $url = UrlTest::getService($this);
        $mailManager = SpipuCoreMock::getMailManager($this);
        $mailManager
            ->expects($this->once())
            ->method('sendHtmlMail')
            ->with(
                '[PROCESS][MOCK] - an error occurs during the execution',
                'from@mock.fr',
                'to@mock.fr'
            );

        $eventDispatcher = SymfonyMock::getEventDispatcher($this);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->anything(), 'spipu.process.log.failed');

        $service = new MailManager($configuration, $mailManager, $url, $eventDispatcher);

        $log = SpipuProcessMock::getLogEntity(1);
        $log->setCode('MOCK');

        $this->assertSame(true, $service->sendAlert($log));
    }
}
