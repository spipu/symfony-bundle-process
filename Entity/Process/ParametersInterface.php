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

interface ParametersInterface
{
    public function setParentParameters(ParametersInterface $parentParameters): void;

    public function get(string $code): mixed;

    public function set(string $code, mixed $value): void;

    public function setDefaultValue(string $code, mixed $value): void;
}
