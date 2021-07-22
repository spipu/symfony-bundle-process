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

class ToTime implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'toTime';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        if (preg_match('/^([0-9]{2})([0-9]{2})$/', $value, $match)) {
            $value = $match[1] . ':' . $match[2] . ':00';
        }

        return $value;
    }
}
