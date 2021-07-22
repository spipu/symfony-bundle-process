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
    /**
     * @var array
     */
    private $values;

    /**
     * @var ParametersInterface
     */
    private $parentParameters;

    /**
     * Parameters constructor.
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    /**
     * @param ParametersInterface $parentParameters
     * @return void
     */
    public function setParentParameters(ParametersInterface $parentParameters): void
    {
        $this->parentParameters = $parentParameters;
    }

    /**
     * Set a value
     * @param string $code
     * @param mixed $value
     * @return void
     */
    public function set(string $code, $value): void
    {
        $this->values[$code] = $value;
    }

    /**
     * Set a default value
     * @param string $code
     * @param mixed $value
     * @return void
     */
    public function setDefaultValue(string $code, $value): void
    {
        if (!array_key_exists($code, $this->values)) {
            $this->values[$code] = $value;
        }
    }

    /**
     * Get a value
     * @param string $code
     * @return mixed
     */
    public function get(string $code)
    {
        $value = array_key_exists($code, $this->values) ? $this->values[$code] : $this->parentParameters->get($code);

        return $this->compute($value);
    }

    /**
     * Compute a value
     * @param mixed $value
     * @return mixed
     */
    private function compute($value)
    {
        if (is_array($value)) {
            return $this->computeArray($value);
        }

        if (is_string($value)) {
            return $this->computeString($value);
        }

        return $value;
    }

    /**
     * Compute an array value
     * @param array $array
     * @return array
     */
    private function computeArray(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $this->compute($value);
        }

        return $array;
    }

    /**
     * Compute a string value
     * @param string $string
     * @return mixed
     */
    private function computeString(string $string)
    {
        if (preg_match('/^{{ ([^}]+) }}$/', $string, $match)) {
            return $this->get($match[1]);
        }

        $string = preg_replace_callback(
            '/{{ ([^}]+) }}/',
            function ($match) {
                return $this->get($match[1]);
            },
            $string
        );

        return $string;
    }
}
