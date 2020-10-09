<?php
/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Spipu\ProcessBundle\Event;

use Spipu\ProcessBundle\Entity\Log as ProcessLog;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Grid Event
 */
class LogFailedEvent extends Event
{
    /**
     * @var ProcessLog
     */
    private $processLog;

    /**
     * @var string
     */
    private $processLogUrl;

    /**
     * GridEvent constructor.
     * @param ProcessLog $processLog
     * @param string $processLogUrl
     */
    public function __construct(ProcessLog $processLog, string $processLogUrl)
    {
        $this->processLog = $processLog;
        $this->processLogUrl = $processLogUrl;
    }

    /**
     * @return string
     */
    public function getEventCode(): string
    {
        return 'spipu.process.log.failed';
    }

    /**
     * @return ProcessLog
     */
    public function getProcessLog(): ProcessLog
    {
        return $this->processLog;
    }

    /**
     * @return string
     */
    public function getProcessLogUrl(): string
    {
        return $this->processLogUrl;
    }
}
