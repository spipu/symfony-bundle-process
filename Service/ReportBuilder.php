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
use Spipu\ProcessBundle\Entity\Process;

class ReportBuilder implements ReportBuilderInterface
{
    /**
     * @var ConfigurationManager
     */
    private ConfigurationManager $configurationManager;

    /**
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param Process\Process $process
     * @return string
     */
    public function buildTitle(Process\Process $process): string
    {
        return sprintf(
            '[%s] Task "%s" report - %s',
            $this->getWebsiteName(),
            $process->getName(),
            $process->getTask()->getStatus()
        );
    }

    /**
     * @param Process\Process $process
     * @param Process\Report $report
     * @return string
     */
    public function buildContent(Process\Process $process, Process\Report $report): string
    {
        return str_replace(
            '{{content}}',
            $this->buildContentSteps($report),
            $this->buildContentTemplate($process)
        );
    }

    /**
     * @param Process\Report $report
     * @return string
     */
    public function buildContentSteps(Process\Report $report): string
    {
        $content = '';
        foreach ($report->getSteps() as $step) {
            $content .= $this->buildContentStep($step);
        }

        return $content;
    }

    /**
     * @param Process\Process $process
     * @return string
     */
    private function buildContentTemplate(Process\Process $process): string
    {
        $websiteName = $this->getWebsiteName();
        $status = $process->getTask()->getStatus();
        $title = "{$websiteName} - Task \"{$process->getName()}\" report - $status";

        return "
<html lang='en'>
    <head>
        <title>{$title}</title>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <style>
            * { font-family: Arial,Helvetica,sans-serif; }
            .text-bold      { font-weight: bold; }
            .text-finished  { color: #28a745; }
            .text-failed    { color: #dc3545; }
            .step-container { margin: 0; padding: 0 0 0.5rem; border: none; background: transparent; }
            .step           { border: solid 1px transparent; padding: 0.5rem; border-radius: .5rem; }
            .step-message   { color: #202020; background-color: #ffffff; border-color: #6c757d; }
            .step-warning   { color: #664d03; background-color: #fff3cd; border-color: #ffc107; }
            .step-error     { color: #842029; background-color: #f8d7da; border-color: #DC3545; }
        </style>
    </head>
    <body>
        <h1>{$title}</h1>
        <h2>Process information</h2>
        <ul>
            <li>Code: {$process->getCode()}</li>
            <li>Name: {$process->getName()}</li>
        </ul>
        <h2>Task information</h2>
        <ul>
            <li>Status:       <span class='text-bold text-{$status}'>{$status}</span></li>
            <li>Progress:     {$process->getTask()->getProgress()} %</li>
            <li>Executed at:  {$process->getTask()->getExecutedAt()->format('Y-m-d H:i:s')}</li>
            <li>Finished at:  {$process->getTask()->getUpdatedAt()->format('Y-m-d H:i:s')}</li>
        </ul>
        <h2>Report</h2>
        {{content}}
        <hr />
        <p>END OF REPORT</p>
    </body>
</html>
";
    }

    /**
     * @param Process\ReportStep $step
     * @return string
     */
    private function buildContentStep(Process\ReportStep $step): string
    {
        $message = $step->getMessage();
        if ($step->getLink()) {
            $link = htmlentities($step->getLink());
            $message .= "<br /><a href='$link' target='_blank'>$link</a>";
        }

        return "<div class='step-container'><div class='step step-{$step->getLevel()}'>{$message}</div></div>\n";
    }

    /**
     * @return string
     */
    public function getWebsiteName(): string
    {
        return (string)$this->configurationManager->get('app.website.name');
    }
}
