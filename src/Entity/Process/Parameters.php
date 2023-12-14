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

class Parameters implements ParametersInterface
{
    private array $values;
    private ?ParametersInterface $parentParameters = null;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function setParentParameters(ParametersInterface $parentParameters): void
    {
        $this->parentParameters = $parentParameters;
    }

    public function set(string $code, mixed $value): void
    {
        $this->values[$code] = $value;
    }

    public function setDefaultValue(string $code, mixed $value): void
    {
        if (!array_key_exists($code, $this->values)) {
            $this->values[$code] = $value;
        }
    }

    public function get(string $code): mixed
    {
        $value = array_key_exists($code, $this->values) ? $this->values[$code] : $this->parentParameters->get($code);

        return $this->compute($value);
    }

    private function compute(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->computeArray($value);
        }

        if (is_string($value)) {
            return $this->computeString($value);
        }

        return $value;
    }

    private function computeArray(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $this->compute($value);
        }

        return $array;
    }

    private function computeString(string $string): mixed
    {
        if (preg_match('/^{{ ([^}]+) }}$/', $string, $match)) {
            return $this->get($match[1]);
        }

        return preg_replace_callback(
            '/{{ ([^}]+) }}/',
            function ($match) {
                return $this->get($match[1]);
            },
            $string
        );
    }
}
