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
        $title = "$websiteName - Task \"{$process->getName()}\" report - $status";

        $statusColor = ($status === 'finished') ? '#28a745' : '#dc3545';

        return "<!doctype html>
<html
    xmlns=\"http://www.w3.org/1999/xhtml\"
    xmlns:v=\"urn:schemas-microsoft-com:vml\" xmlns:o=\"urn:schemas-microsoft-com:office:office\"
    lang=\"en\"
>
    <head>
        <title>$title</title>
        <!--[if !mso]><!-->
        <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
        <!--<![endif]-->
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    </head>
    <body style=\"font-family: Arial,Helvetica,sans-serif;\">
        <h1>$title</h1>
        <h2>Process information</h2>
        <ul>
            <li>Code: {$process->getCode()}</li>
            <li>Name: {$process->getName()}</li>
        </ul>
        <h2>Task information</h2>
        <ul>
            <li>Status:       <span style=\"font-weight: bold; color: $statusColor\">$status</span></li>
            <li>Progress:     {$process->getTask()->getProgress()} %</li>
            <li>Executed at:  {$process->getTask()->getExecutedAt()->format('Y-m-d H:i:s')}</li>
            <li>Finished at:  {$process->getTask()->getUpdatedAt()->format('Y-m-d H:i:s')}</li>
        </ul>
        <h2>Report</h2>
        <table>
        {{content}}
        </table>
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
            $message .= "<br /><a href=\"$link\" target=\"_blank\">$link</a>";
        }

        $styleTemplate = 'border: solid 1px %s; padding: 10px; border-radius: 10px; color: %s; background-color: %s;';
        switch ($step->getLevel()) {
            case 'error':
                $style = sprintf($styleTemplate, '#DC3545', '#842029', '#f8d7da');
                break;

            case 'warning':
                $style = sprintf($styleTemplate, '#ffc107', '#664d03', '#fff3cd');
                break;

            case 'message':
            default:
                $style = sprintf($styleTemplate, '#6c757d', '#202020', '#ffffff');
                break;
        }

        return "
<tr><td style=\"$style\">$message</td></tr>
<tr><td style=\"padding: 0; border: none; background: transparent; height: 10px\"></td></tr>";
    }

    /**
     * @return string
     */
    public function getWebsiteName(): string
    {
        return (string)$this->configurationManager->get('app.website.name');
    }
}
