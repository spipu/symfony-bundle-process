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

use Spipu\ProcessBundle\Entity\Process;

interface ReportBuilderInterface
{
    /**
     * @param Process\Process $process
     * @return string
     */
    public function buildTitle(Process\Process $process): string;

    /**
     * @param Process\Process $process
     * @param Process\Report $report
     * @return string
     */
    public function buildContent(Process\Process $process, Process\Report $report): string;
}
