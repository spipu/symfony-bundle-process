<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\InputException;

class Input
{
    const AVAILABLE_TYPES = ['string', 'int', 'float', 'bool', 'array'];

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $type;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Input constructor.
     * @param string $name
     * @param string $type
     * @throws InputException
     */
    public function __construct(
        string $name,
        string $type
    ) {
        if (!in_array($type, static::AVAILABLE_TYPES)) {
            throw new InputException(
                sprintf('[%s] type for [%s] input is not allowed', $type, $name)
            );
        }

        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param mixed $value
     * @return void
     * @throws InputException
     */
    public function setValue($value): void
    {
        if ($this->type === 'float' && is_int($value)) {
            $value = (float) $value;
        }

        if (!call_user_func('is_'.$this->type, $value)) {
            throw new InputException(sprintf('[%s] must be an %s', $this->name, $this->type));
        }

        $this->value = $value;
    }

    /**
     * @return mixed
     * @throws InputException
     */
    public function getValue()
    {
        $this->validate();

        return $this->value;
    }

    /**
     * @return bool
     * @throws InputException
     */
    public function validate(): bool
    {
        if ($this->value === null) {
            throw new InputException(sprintf('[%s] input is not set', $this->name));
        }

        return true;
    }
}
