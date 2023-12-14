<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spipu\ProcessBundle\Service;

use Spipu\CoreBundle\Service\MailManager as BaseMailManager;
use Spipu\ProcessBundle\Entity\Log as ProcessLog;
use Spipu\ProcessBundle\Event\LogFailedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

class MailManager
{
    private ModuleConfiguration $configuration;
    private BaseMailManager $mailManager;
    private Url $url;
    private EventDispatcherInterface $eventDispatcher;

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

    public function sendAlert(ProcessLog $processLog, ?Throwable $exception = null): bool
    {
        $logUrl = $this->url->getLogUrl($processLog->getId());

        $event = new LogFailedEvent($processLog, $logUrl, $exception);
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
