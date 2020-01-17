<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\InputException;
use Spipu\UiBundle\Form\Options\AbstractOptions;

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
     * @var AbstractOptions|null
     */
    private $options;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Input constructor.
     * @param string $name
     * @param string $type
     * @param AbstractOptions|null $options
     * @throws InputException
     */
    public function __construct(
        string $name,
        string $type,
        AbstractOptions $options = null
    ) {
        if (!in_array($type, static::AVAILABLE_TYPES)) {
            throw new InputException(
                sprintf('[%s] type for [%s] input is not allowed', $type, $name)
            );
        }

        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
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
     * @return AbstractOptions|null
     */
    public function getOptions(): ?AbstractOptions
    {
        return $this->options;
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

        if ($this->options !== null && !$this->options->hasKey($value)) {
            throw new InputException(sprintf('[%s] This value is not authorized', $this->name));
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
