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

namespace Spipu\ProcessBundle\Step;

use Spipu\ProcessBundle\Entity\Process\Report;

trait StepReportTrait
{
    private ?Report $report = null;

    public function setReport(?Report $report): void
    {
        $this->report = $report;
    }

    protected function addReportMessage(string $message, ?string $link = null): void
    {
        if ($this->report !== null) {
            $this->report->addMessage($message, $link);
        }
    }

    protected function addReportWarning(string $message, ?string $link = null): void
    {
        if ($this->report !== null) {
            $this->report->addWarning($message, $link);
        }
    }

    protected function addReportError(string $message, ?string $link = null): void
    {
        if ($this->report !== null) {
            $this->report->addError($message, $link);
        }
    }
}
