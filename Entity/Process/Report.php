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

namespace Spipu\ProcessBundle\Entity\Process;

class Report
{
    /**
     * @var string
     */
    private string $email;

    /**
     * @var ReportStep[]
     */
    private array $steps = [];

    /**
     * Report constructor.
     * @param string $email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $message
     * @param string|null $link
     * @return void
     */
    public function addMessage(string $message, ?string $link = null): void
    {
        $this->addStep($message, 'message', $link);
    }

    /**
     * @param string $message
     * @param string|null $link
     * @return void
     */
    public function addWarning(string $message, ?string $link = null): void
    {
        $this->addStep($message, 'warning', $link);
    }

    /**
     * @param string $message
     * @param string|null $link
     * @return void
     */
    public function addError(string $message, ?string $link = null): void
    {
        $this->addStep($message, 'error', $link);
    }

    /**
     * @param string $message
     * @param string $level
     * @param string|null $link
     * @return void
     */
    private function addStep(string $message, string $level, ?string $link = null): void
    {
        $step = new ReportStep($message, $level, $link);

        $this->steps[] = $step;
    }

    /**
     * @return ReportStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return int
     */
    public function getNbSteps(): int
    {
        return count($this->steps);
    }
}
