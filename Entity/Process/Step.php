<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Step\StepInterface;

class Step
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * @var StepInterface
     */
    private $processor;

    /**
     * @var bool
     */
    private $ignoreInProgress;

    /**
     * Process constructor.
     * @param string $code
     * @param StepInterface $processor
     * @param Parameters $parameters
     * @param bool $ignoreInProgress
     */
    public function __construct(
        string $code,
        StepInterface $processor,
        Parameters $parameters,
        bool $ignoreInProgress
    ) {
        $this->code = $code;
        $this->processor = $processor;
        $this->parameters = $parameters;
        $this->ignoreInProgress = $ignoreInProgress;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return StepInterface
     */
    public function getProcessor(): StepInterface
    {
        return $this->processor;
    }

    /**
     * @return Parameters
     */
    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    /**
     * @return bool
     */
    public function isIgnoreInProgress(): bool
    {
        return $this->ignoreInProgress;
    }
}
