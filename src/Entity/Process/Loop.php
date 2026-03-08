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

class Loop
{
    private string $code;
    private array $steps;
    private Parameters $parameters;
    private bool $ignoreInProgress;

    public function __construct(
        string $code,
        array $steps,
        Parameters $parameters,
        bool $ignoreInProgress
    ) {
        $this->code = $code;
        $this->steps = $steps;
        $this->parameters = $parameters;
        $this->ignoreInProgress = $ignoreInProgress;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSteps(): array
    {
        return $this->steps;
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
