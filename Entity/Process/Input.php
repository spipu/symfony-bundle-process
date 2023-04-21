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
use Spipu\UiBundle\Form\Options\AbstractOptions;

class Input
{
    public const AVAILABLE_TYPES = ['string', 'int', 'float', 'bool', 'array', 'file'];

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $type;

    /**
     * @var bool
     */
    private $required = true;

    /**
     * @var AbstractOptions|null
     */
    private $options;

    /**
     * @var string[]
     */
    private $allowedMimeTypes;

    /**
     * @var string|null
     */
    private $regexp;

    /**
     * @var string|null
     */
    private $help;

    /**
     * @var mixed
     */
    private $value;

    /**
     * Input constructor.
     * @param string $name
     * @param string $type
     * @param bool $required
     * @param AbstractOptions|null $options
     * @param array $allowedMimeTypes
     * @param string|null $regexp
     * @param string|null $help
     * @throws InputException
     */
    public function __construct(
        string $name,
        string $type,
        bool $required,
        ?AbstractOptions $options = null,
        array $allowedMimeTypes = [],
        ?string $regexp = null,
        ?string $help = null
    ) {
        if (!in_array($type, static::AVAILABLE_TYPES)) {
            throw new InputException(
                sprintf('[%s] type for [%s] input is not allowed', $type, $name)
            );
        }

        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->options = $options;
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->regexp = $regexp;
        $this->help = $help;
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
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return AbstractOptions|null
     */
    public function getOptions(): ?AbstractOptions
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @param mixed $value
     * @return void
     * @throws InputException
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     */
    public function setValue($value): void
    {
        $value = $this->prepareValue($value);

        if ($this->required && ($value === null || (is_array($value) && count($value) === 0))) {
            throw new InputException(sprintf('[%s] is required', $this->name));
        }

        if ($value === null) {
            $this->value = null;
            return;
        }

        $this->validateValueType($value);
        $this->validateValueOptions($value);
        $this->validateValueRegexp($value);

        $this->value = $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function prepareValue($value)
    {
        if ($this->type === 'float' && is_int($value)) {
            $value = (float) $value;
        }

        if ($value === '') {
            $value = null;
        }

        if ($this->type === 'array' && $value === null) {
            $value = [];
        }

        return $value;
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
        if ($this->value === null && $this->required) {
            throw new InputException(sprintf('[%s] input is not set', $this->name));
        }

        return true;
    }

    /**
     * @return string|null
     */
    public function getRegexp(): ?string
    {
        return $this->regexp;
    }

    /**
     * @return string|null
     */
    public function getHelp(): ?string
    {
        return $this->help;
    }

    /**
     * @param mixed $value
     * @return void
     * @throws InputException
     */
    private function validateValueType($value): void
    {
        if (!call_user_func('is_' . $this->type, $value)) {
            throw new InputException(sprintf('[%s] must be an %s', $this->name, $this->type));
        }
    }

    /**
     * @param mixed $value
     * @return void
     * @throws InputException
     */
    private function validateValueOptions($value): void
    {
        if ($this->options !== null) {
            $list = is_array($value) ? $value : [$value];
            foreach ($list as $key) {
                if (!$this->options->hasKey($key)) {
                    throw new InputException(sprintf('[%s] This value is not authorized', $this->name));
                }
            }
        }
    }

    /**
     * @param mixed $value
     * @return void
     * @throws InputException
     */
    private function validateValueRegexp($value): void
    {
        if ($this->regexp !== null && is_string($value) && !preg_match($this->regexp, $value)) {
            throw new InputException(sprintf('[%s] This value is not validated by the regexp', $this->name));
        }
    }
}
