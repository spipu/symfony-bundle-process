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

use Spipu\ProcessBundle\Step\StepInterface;

class Step
{
    private string $code;
    private Parameters $parameters;
    private StepInterface $processor;
    private bool $ignoreInProgress;

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

    public function getCode(): string
    {
        return $this->code;
    }

    public function getProcessor(): StepInterface
    {
        return $this->processor;
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    public function isIgnoreInProgress(): bool
    {
        return $this->ignoreInProgress;
    }
}
