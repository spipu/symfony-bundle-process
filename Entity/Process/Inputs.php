<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\InputException;

class Inputs
{
    /**
     * @var Input[]
     */
    private $inputs = [];

    /**
     * @param Input $input
     * @return void
     */
    public function addInput(Input $input): void
    {
        $this->inputs[$input->getName()] = $input;
    }

    /**
     * @return bool
     * @throws InputException
     */
    public function validate(): bool
    {
        foreach ($this->inputs as $input) {
            $input->validate();
        }

        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws InputException
     */
    public function set(string $key, $value): void
    {
        $this->getInput($key)->setValue($value);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws InputException
     */
    public function get(string $key)
    {
        return $this->getInput($key)->getValue();
    }

    /**
     * @return array
     * @throws InputException
     */
    public function getAll(): array
    {
        $values = [];
        foreach ($this->inputs as $input) {
            $values[$input->getName()] = $input->getValue();
        }

        return $values;
    }

    /**
     * @return Input[]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @param string $key
     * @return Input
     * @throws InputException
     */
    public function getInput(string $key): Input
    {
        if (!array_key_exists($key, $this->inputs)) {
            throw new InputException(sprintf('[%s] input is not authorized', $key));
        }

        return $this->inputs[$key];
    }
}
