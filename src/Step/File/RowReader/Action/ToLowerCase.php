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

class ToLowerCase implements ActionInterface
{
    public function getCode(): string
    {
        return 'toLowerCase';
    }

    public function execute(?string $value, array $parameters = []): ?string
    {
        return mb_convert_case($value, MB_CASE_LOWER);
    }
}
