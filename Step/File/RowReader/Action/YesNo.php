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

class YesNo implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'yesNo';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     * @throws RowReaderException
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        $valueYes  = ['y', 'o', '1'];
        $valueNo   = ['n', '0'];
        $valueNull = [''];

        if (!empty($parameters['invert'])) {
            list($valueYes, $valueNo) = [$valueNo, $valueYes];
        }

        if ($value === null) {
            return null;
        }

        $value = mb_convert_case($value, MB_CASE_LOWER);
        if (in_array($value, $valueYes)) {
            return '1';
        }

        if (in_array($value, $valueNo)) {
            return '0';
        }

        if (in_array($value, $valueNull)) {
            return null;
        }

        throw new RowReaderException('Unknown Value for YesNo action');
    }
}
