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
    public const AVAILABLE_TYPES = ['string', 'int', 'float', 'bool', 'array', 'file', 'date', 'datetime' ];

    private string $name;
    private string $type;
    private string $realType;
    private bool $required = true;
    private ?AbstractOptions $options;
    private ?string $regexp;
    private ?string $help;
    private mixed $value = null;

    /**
     * @var string[]
     */
    private array $allowedMimeTypes;

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

        $this->saveProperties($type, $required, $options, $allowedMimeTypes, $regexp, $help);
    }

    private function saveProperties(
        string $type,
        bool $required,
        ?AbstractOptions $options,
        array $allowedMimeTypes,
        ?string $regexp,
        ?string $help
    ): void {
        $this->realType = $type;
        $this->required = $required;
        $this->help = $help;

        switch ($type) {
            case 'date':
                $this->type = 'string';
                $this->regexp = '/^\d{4}-\d{2}-\d{2}$/';
                $this->options = null;
                $this->allowedMimeTypes = [];
                break;

            case 'datetime':
                $this->type = 'string';
                $this->regexp = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
                $this->options = null;
                $this->allowedMimeTypes = [];
                break;

            default:
                $this->type = $type;
                $this->regexp = $regexp;
                $this->options = $options;
                $this->allowedMimeTypes = $allowedMimeTypes;
                break;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRealType(): string
    {
        return $this->realType;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getOptions(): ?AbstractOptions
    {
        return $this->options;
    }

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
    public function setValue(mixed $value): void
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

    private function prepareValue(mixed $value): mixed
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

    public function getValue(): mixed
    {
        $this->validate();

        return $this->value;
    }

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

    public function getHelp(): ?string
    {
        return $this->help;
    }

    private function validateValueType(mixed $value): void
    {
        if (!call_user_func('is_' . $this->type, $value)) {
            throw new InputException(sprintf('[%s] must be an %s', $this->name, $this->type));
        }
    }

    private function validateValueOptions(mixed $value): void
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

    private function validateValueRegexp(mixed $value): void
    {
        if ($this->regexp !== null && is_string($value) && !preg_match($this->regexp, $value)) {
            throw new InputException(sprintf('[%s] This value is not validated by the regexp', $this->name));
        }
    }
}
