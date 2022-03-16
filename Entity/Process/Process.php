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
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * @var Step[]
     */
    private $steps;

    /**
     * @var Inputs
     */
    private $inputs;
    /**
     * @var Options
     */
    private $options;

    /**
     * @var Task|null
     */
    private $task;

    /**
     * @var int|null
     */
    private $logId;

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

    /**
     * Link the process parameters to each step parameters
     * @return void
     */
    private function linkParameters(): void
    {
        foreach ($this->steps as $step) {
            $step->getParameters()->setParentParameters($this->parameters);
        }
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Options
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * @return Inputs
     */
    public function getInputs(): Inputs
    {
        return $this->inputs;
    }

    /**
     * @return Parameters
     */
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

    /**
     * @param Task $task
     * @return Process
     */
    public function setTask(Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    /**
     * @return Task|null
     */
    public function getTask(): ?Task
    {
        return $this->task;
    }

    /**
     * @return int|null
     */
    public function getLogId(): ?int
    {
        return $this->logId;
    }

    /**
     * @param int|null $logId
     * @return self
     */
    public function setLogId(?int $logId): self
    {
        $this->logId = $logId;

        return $this;
    }
}
