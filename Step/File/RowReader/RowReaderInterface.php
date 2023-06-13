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

namespace Spipu\ProcessBundle\Step\File\RowReader;

interface RowReaderInterface
{
    public function init(): void;

    public function setParameters(array $parameters): void;

    public function setFields(array $definitions): void;

    public function setGlobalActions(array $actions): void;

    public function read($fileHandler): ?array;

    public function getNbReadLines(): int;

    public function getNbAcceptedLines(): int;
}
