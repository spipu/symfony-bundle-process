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

namespace Spipu\ProcessBundle\Event;

use Spipu\ProcessBundle\Entity\Log as ProcessLog;
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

class LogFailedEvent extends Event
{
    private ProcessLog $processLog;
    private string $processLogUrl;
    private ?Throwable $exception;

    public function __construct(ProcessLog $processLog, string $processLogUrl, ?Throwable $exception = null)
    {
        $this->processLog = $processLog;
        $this->processLogUrl = $processLogUrl;
        $this->exception = $exception;
    }

    public function getEventCode(): string
    {
        return 'spipu.process.log.failed';
    }

    public function getProcessLog(): ProcessLog
    {
        return $this->processLog;
    }

    public function getProcessLogUrl(): string
    {
        return $this->processLogUrl;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }
}
