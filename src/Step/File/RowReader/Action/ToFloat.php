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

class ToFloat implements ActionInterface
{
    public const DEFAULT_DECIMAL = 4;
    public const DEFAULT_SEPARATOR = '.';

    public function getCode(): string
    {
        return 'toFloat';
    }

    public function execute(?string $value, array $parameters = []): ?string
    {
        if (!isset($parameters['decimal'])) {
            $parameters['decimal'] = self::DEFAULT_DECIMAL;
        }

        if (!isset($parameters['separator'])) {
            $parameters['separator'] = self::DEFAULT_SEPARATOR;
        }

        $decimalPart = substr($value, -1 * $parameters['decimal'], $parameters['decimal']);
        $integerPart = substr($value, 0, strlen($value) - strlen(($decimalPart)));

        return $integerPart . $parameters['separator'] . $decimalPart;
    }
}
