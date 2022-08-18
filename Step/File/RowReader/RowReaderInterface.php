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

use Spipu\ProcessBundle\Exception\RowReaderException;

interface RowReaderInterface
{
    /**
     * Init the row reader
     * @return void
     */
    public function init(): void;

    /**
     * Init the parameters
     * @param array $parameters
     * @return void
     * @throws RowReaderException
     */
    public function setParameters(array $parameters): void;

    /**
     * Init the fields
     * @param array $definitions
     * @return void
     * @throws RowReaderException
     */
    public function setFields(array $definitions): void;

    /**
     * Init the global actions
     * @param array $actions
     * @return void
     */
    public function setGlobalActions(array $actions): void;

    /**
     * Read a line
     * @param resource $fileHandler
     * @return null|array
     * @throws RowReaderException
     */
    public function read($fileHandler): ?array;

    /**
     * Get the number of read lines
     * @return int
     */
    public function getNbReadLines(): int;

    /**
     * Get the number of accepted lines
     * @return int
     */
    public function getNbAcceptedLines(): int;
}
