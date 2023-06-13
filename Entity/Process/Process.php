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

use Spipu\ProcessBundle\Entity\Task;

class Process
{
    private string $code;
    private string $name;
    private Options $options;
    private Inputs $inputs;
    private Parameters $parameters;

    /**
     * @var Step[]
     */
    private array $steps;
    private ?Task $task = null;
    private ?int $logId = null;
    private ?Report $report = null;


    /**
     * Process constructor.
     * @param string $code
     * @param string $name
     * @param Options $options
     * @param Inputs $inputs
     * @param Parameters $parameters
     * @param Step[] $steps
     */
    public function __construct(
        string $code,
        string $name,
        Options $options,
        Inputs $inputs,
        Parameters $parameters,
        array $steps
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->options = $options;
        $this->inputs = $inputs;
        $this->parameters = $parameters;
        $this->steps = $steps;

        $this->linkParameters();
    }

    private function linkParameters(): void
    {
        foreach ($this->steps as $step) {
            $step->getParameters()->setParentParameters($this->parameters);
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getInputs(): Inputs
    {
        return $this->inputs;
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    /**
     * @return Step[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function setTask(Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function getLogId(): ?int
    {
        return $this->logId;
    }

    public function setLogId(?int $logId): self
    {
        $this->logId = $logId;

        return $this;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(Report $report): self
    {
        $this->report = $report;

        return $this;
    }
}
