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
    public function buildTitle(Process\Process $process): string;

    public function buildContent(Process\Process $process, Process\Report $report): string;
}
