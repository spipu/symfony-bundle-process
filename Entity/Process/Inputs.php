<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\InputException;

class Inputs
{
    const AVAILABLE_TYPES = ['string', 'int', 'float', 'bool', 'array'];

    /**
     * @var array
     */
    private $definition;

    /**
     * @var array
     */
    private $data = [];

    /**
     * Inputs constructor.
     * @param array $definition
     * @throws InputException
     */
    public function __construct(
        array $definition
    ) {
        $this->definition = $definition;

        $this->validateDefinition();
    }

    /**
     * @return void
     * @throws InputException
     */
    private function validateDefinition(): void
    {
        foreach ($this->definition as $key => $type) {
            if (!in_array($type, static::AVAILABLE_TYPES)) {
                throw new InputException(sprintf('[%s] type for [%s] input is not allowed', $type, $key));
            }
        }
    }

    /**
     * @return bool
     * @throws InputException
     */
    public function validate(): bool
    {
        foreach (array_keys($this->definition) as $key) {
            if (!array_key_exists($key, $this->data)) {
                throw new InputException(sprintf('[%s] input is not set', $key));
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws InputException
     */
    public function set(string $key, $value): void
    {
        if (!array_key_exists($key, $this->definition)) {
            throw new InputException(sprintf('[%s] input is not authorized', $key));
        }

        $this->validateValue($key, $value);
        $this->data[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     * @throws InputException
     */
    private function validateValue(string $key, $value): bool
    {
        $type = $this->definition[$key];

        if (!call_user_func('is_'.$type, $value)) {
            throw new InputException(sprintf('[%s] must be an %s', $key, $type));
        }

        return true;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws InputException
     */
    public function get(string $key)
    {
        if (!array_key_exists($key, $this->definition)) {
            throw new InputException(sprintf('[%s] input is not authorized', $key));
        }

        if (!array_key_exists($key, $this->data)) {
            throw new InputException(sprintf('[%s] input is not set', $key));
        }

        return $this->data[$key];
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->data;
    }
}
