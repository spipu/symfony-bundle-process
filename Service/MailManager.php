<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\CoreBundle\Service\MailManager as BaseMailManager;
use Spipu\ProcessBundle\Entity\Log as ProcessLog;

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
     * Mailer constructor.
     * @param ModuleConfiguration $configuration
     * @param BaseMailManager $mailManager
     * @param Url $url
     */
    public function __construct(
        ModuleConfiguration $configuration,
        BaseMailManager $mailManager,
        Url $url
    ) {
        $this->configuration = $configuration;
        $this->mailManager = $mailManager;
        $this->url = $url;
    }

    /**
     * @param ProcessLog $processLog
     * @return bool
     */
    public function sendAlert(ProcessLog $processLog): bool
    {
        if (!$this->configuration->hasFailedSendEmail()) {
            return false;
        }

        $logUrl = $this->url->getLogUrl($processLog->getId());

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
