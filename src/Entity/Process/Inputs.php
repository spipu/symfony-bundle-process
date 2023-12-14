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

use Spipu\ProcessBundle\Exception\InputException;

class Inputs
{
    /**
     * @var Input[]
     */
    private array $inputs = [];

    public function addInput(Input $input): void
    {
        $this->inputs[$input->getName()] = $input;
    }

    public function validate(): bool
    {
        foreach ($this->inputs as $input) {
            $input->validate();
        }

        return true;
    }

    public function set(string $key, mixed $value): void
    {
        $this->getInput($key)->setValue($value);
    }

    public function get(string $key): mixed
    {
        return $this->getInput($key)->getValue();
    }

    public function getAll(): array
    {
        $values = [];
        foreach ($this->inputs as $input) {
            $values[$input->getName()] = $input->getValue();
        }

        return $values;
    }

    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function getInput(string $key): Input
    {
        if (!array_key_exists($key, $this->inputs)) {
            throw new InputException(sprintf('[%s] input is not authorized', $key));
        }

        return $this->inputs[$key];
    }
}
