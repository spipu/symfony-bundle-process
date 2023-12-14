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

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

use Spipu\ProcessBundle\Exception\RowReaderException;

class ToInteger implements ActionInterface
{
    public function getCode(): string
    {
        return 'toInteger';
    }

    public function execute(?string $value, array $parameters = []): ?string
    {
        if ($value === '') {
            $value = null;
        }

        if ($value !== null) {
            $value = preg_replace('/^[\+]?[0]*([0-9]+)$/', '$1', $value);
            if (!preg_match('/^[0-9]+$/', $value)) {
                throw new RowReaderException('Invalid Integer Value: [' . $value . ']');
            }
        }

        return $value;
    }
}
