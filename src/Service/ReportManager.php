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

use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Spipu\CoreBundle\Service\MailManager;
use Spipu\ProcessBundle\Entity\Process;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Service\Url as ProcessUrl;
use Spipu\ProcessBundle\Step\StepInterface;
use Spipu\ProcessBundle\Step\StepReportInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ReportManager
{
    public const AUTOMATIC_REPORT_EMAIL_FIELD = 'automatic_report_email';

    private MailManager $mailManager;
    private Url $processUrl;
    private ReportBuilderInterface $reportBuilder;
    private ConfigurationManager $configurationManager;

    public function __construct(
        MailManager $mailManager,
        ProcessUrl $processUrl,
        ReportBuilderInterface $reportBuilder,
        ConfigurationManager $configurationManager
    ) {
        $this->mailManager = $mailManager;
        $this->processUrl = $processUrl;
        $this->reportBuilder = $reportBuilder;
        $this->configurationManager = $configurationManager;
    }

    public function prepareReport(Process\Process $process, LoggerProcessInterface $logger): void
    {
        if (!$process->getOptions()->hasAutomaticReport()) {
            return;
        }

        $email = $process->getInputs()->get(self::AUTOMATIC_REPORT_EMAIL_FIELD);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InputException('The automatic report email is invalid: ' . $email);
        }

        $email = $process->getInputs()->get(self::AUTOMATIC_REPORT_EMAIL_FIELD);
        $report = new Process\Report($email);
        $process->setReport($report);

        $logger->debug(sprintf('Automatic report will be sent to [%s]', $email));

        $report->addMessage(
            'You can click on the following link to see the process task',
            $this->processUrl->getTaskUrl($process->getTask()->getId())
        );
        $report->addMessage(
            'You can click on the following link to see the process log',
            $this->processUrl->getLogUrl($logger->getModel()->getId())
        );
    }

    public function addReportToStep(StepInterface $stepProcessor, ?Process\Report $report): void
    {
        if ($stepProcessor instanceof StepReportInterface) {
            $stepProcessor->setReport($report);
        }
    }

    public function addProcessReportMessage(Process\Process $process, string $message): void
    {
        if ($process->getReport()) {
            $process->getReport()->addMessage($message);
        }
    }

    public function addProcessReportWarning(Process\Process $process, string $message): void
    {
        if ($process->getReport()) {
            $process->getReport()->addWarning($message);
        }
    }

    public function addProcessReportError(Process\Process $process, string $message): void
    {
        if ($process->getReport()) {
            $process->getReport()->addError($message);
        }
    }

    public function sendReport(Process\Process $process): void
    {
        if ($process->getReport() === null) {
            return;
        }

        $report = $process->getReport();

        $title      = $this->reportBuilder->buildTitle($process);
        $content    = $this->reportBuilder->buildContent($process, $report);
        $emailFrom  = $this->configurationManager->get('app.email.sender');
        $emailTo    = $report->getEmail();

        try {
            $this->mailManager->sendHtmlMail($title, $emailFrom, $emailTo, $content);
        } catch (TransportExceptionInterface $e) {
            // If the report is not sent, just ignore the error.
        }
    }
}
