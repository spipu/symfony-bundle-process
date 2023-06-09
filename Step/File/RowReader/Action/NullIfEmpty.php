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

class NullIfEmpty implements ActionInterface
{
    public function getCode(): string
    {
        return 'nullIfEmpty';
    }

    public function execute(?string $value, array $parameters = []): ?string
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }
}
