<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\CoreBundle\Service\MailManager as BaseMailManager;
use Spipu\ProcessBundle\Entity\Log as ProcessLog;
use Spipu\ProcessBundle\Event\LogFailedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MailManager
{
    /**
     * @var ModuleConfiguration
     */
    private $configuration;

    /**
     * @var BaseMailManager
     */
    private $mailManager;

    /**
     * @var Url
     */
    private $url;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Mailer constructor.
     * @param ModuleConfiguration $configuration
     * @param BaseMailManager $mailManager
     * @param Url $url
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ModuleConfiguration $configuration,
        BaseMailManager $mailManager,
        Url $url,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configuration = $configuration;
        $this->mailManager = $mailManager;
        $this->url = $url;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProcessLog $processLog
     * @return bool
     */
    public function sendAlert(ProcessLog $processLog): bool
    {
        $logUrl = $this->url->getLogUrl($processLog->getId());

        $event = new LogFailedEvent($processLog, $logUrl);
        $this->eventDispatcher->dispatch($event, $event->getEventCode());

        if (!$this->configuration->hasFailedSendEmail()) {
            return false;
        }


        $message = "
    Hi,
    
    This is an automatic important technical message.
    
    An <b>error</b> occurs during the execution of the following process: <b>{$processLog->getCode()}</b>.
    You can click on the following link for more details:
    
    <a href='$logUrl'>$logUrl</a>
    
    --== END OF MESSAGE ==--
        ";

        $this->mailManager->sendHtmlMail(
            "[PROCESS][{$processLog->getCode()}] - an error occurs during the execution",
            $this->configuration->getFailedEmailFrom(),
            $this->configuration->getFailedEmailTo(),
            nl2br($message)
        );

        return true;
    }
}
