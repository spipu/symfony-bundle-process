<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Entity\Process;

interface ParametersInterface
{
    /**
     * @param ParametersInterface $parentParameters
     * @return void
     */
    public function setParentParameters(ParametersInterface $parentParameters): void;

    /**
     * Get a value
     * @param string $code
     * @return mixed
     */
    public function get(string $code);

    /**
     * Set a value
     * @param string $code
     * @param mixed $value
     * @return void
     */
    public function set(string $code, $value): void;

    /**
     * Set a default value
     * @param string $code
     * @param mixed $value
     * @return void
     */
    public function setDefaultValue(string $code, $value): void;
}
